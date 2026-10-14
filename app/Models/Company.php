<?php

namespace App\Models;

use App\Traits\HasConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Company extends Authenticatable
{
    use HasApiTokens, HasConfig, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'ruc',
        'address',
        'phone',
        'email',
        'password',
        'monthly_fee',
        'billing_cycle',
        'trial_until',
        'paid_until',
    ];

    // Estados de la mensualidad (ver subscriptionStatus())
    const SUB_EXEMPT    = 'exempt';    // plan gratis: nunca se cobra (paid_until vacío)
    const SUB_TRIAL     = 'trial';     // periodo de prueba gratis, aún sin pagos
    const SUB_ACTIVE    = 'active';    // al día
    const SUB_DUE_SOON  = 'due_soon';  // vence en pocos días: aviso ámbar
    const SUB_OVERDUE   = 'overdue';   // vencida, dentro de los días de gracia: aviso rojo
    const SUB_SUSPENDED = 'suspended'; // vencida y sin gracia: acceso bloqueado

    const SUB_LABELS = [
        self::SUB_EXEMPT    => 'Gratis',
        self::SUB_TRIAL     => 'En prueba',
        self::SUB_ACTIVE    => 'Al día',
        self::SUB_DUE_SOON  => 'Por vencer',
        self::SUB_OVERDUE   => 'Vencida',
        self::SUB_SUSPENDED => 'Suspendida',
    ];

    // Ciclo de cobro: cuándo vence cada mes pagado
    const CYCLE_DATE      = 'date';      // cada mes el mismo día (21/10 → 20/11 → 20/12)
    const CYCLE_MONTH_END = 'month_end'; // por mes calendario, vence el último día del mes

    const CYCLE_LABELS = [
        self::CYCLE_DATE      => 'Por fecha (cada mes el mismo día)',
        self::CYCLE_MONTH_END => 'A fin de mes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'monthly_fee'       => 'decimal:2',
        'trial_until'       => 'date',
        'paid_until'        => 'date',
    ];

    // ── Mensualidad del servicio ────────────────────────────────

    /**
     * Estado de la mensualidad hoy: exempt, trial, active, due_soon, overdue o suspended.
     * El servicio está cubierto hasta el día paid_until inclusive.
     */
    public function subscriptionStatus(): string
    {
        if (!$this->paid_until) {
            return self::SUB_EXEMPT;
        }

        $days = $this->subscriptionDaysLeft();

        if ($days >= 0) {
            if ($this->isTrialCoverage()) {
                return self::SUB_TRIAL;
            }

            return $days <= config('billing.warning_days') ? self::SUB_DUE_SOON : self::SUB_ACTIVE;
        }

        return -$days <= config('billing.grace_days') ? self::SUB_OVERDUE : self::SUB_SUSPENDED;
    }

    /**
     * ¿Lo cubierto es solo el periodo de prueba? (aún no registró pagos que lo extiendan)
     * Sirve para decir "tu prueba termina / terminó" en lugar de "tu mensualidad vence / venció".
     */
    public function isTrialCoverage(): bool
    {
        return $this->trial_until && $this->paid_until && $this->paid_until->lte($this->trial_until);
    }

    /**
     * Último día que cubre un pago de $months meses contados desde $base (el vencimiento actual).
     *   Por fecha:   base 20/10 + 1 mes → 20/11
     *   Fin de mes:  base 20/10 + 1 mes → 30/11 (el primer pago completa el mes en curso)
     */
    public function periodEndFor(\Carbon\Carbon $base, int $months): \Carbon\Carbon
    {
        $end = $base->copy()->addMonthsNoOverflow($months);

        return $this->billing_cycle === self::CYCLE_MONTH_END ? $end->endOfMonth()->startOfDay() : $end;
    }

    /**
     * Vencimiento actual desde el que se cuenta el próximo pago (si nunca tuvo fecha, ayer: cubre desde hoy).
     */
    public function nextPaymentBase(): \Carbon\Carbon
    {
        return ($this->paid_until ?? today()->subDay())->copy();
    }

    /**
     * Días que faltan para el vencimiento (0 = vence hoy, negativo = días de atraso).
     */
    public function subscriptionDaysLeft(): ?int
    {
        return $this->paid_until ? (int) today()->diffInDays($this->paid_until, false) : null;
    }

    /**
     * Fecha a partir de la cual se bloquea el acceso (último día de gracia + 1).
     */
    public function subscriptionBlocksOn(): ?\Carbon\Carbon
    {
        return $this->paid_until?->copy()->addDays(config('billing.grace_days') + 1);
    }

    /**
     * ¿Puede usar el sistema? (bloqueado solo cuando la mensualidad está suspendida)
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptionStatus() !== self::SUB_SUSPENDED;
    }

    /**
     * Registra un pago de la mensualidad y extiende paid_until los meses pagados según el ciclo de cobro.
     * Los meses se cuentan desde el vencimiento actual (si nunca pagó, desde hoy),
     * así un pago atrasado cubre primero los meses que debía.
     */
    public function registerPayment(array $data, int $adminId): SubscriptionPayment
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $adminId) {
            // Bloquea la fila de la empresa para que dos pagos simultáneos no calculen el mismo periodo
            $company = self::whereKey($this->id)->lockForUpdate()->first();

            $base     = $company->nextPaymentBase();
            $periodTo = $company->periodEndFor($base, (int) $data['months']);

            $payment = $company->subscriptionPayments()->create([
                'admin_id'         => $adminId,
                'amount'           => $data['amount'],
                'months'           => $data['months'],
                'period_from'      => $base->copy()->addDay(),
                'period_to'        => $periodTo,
                'paid_at'          => $data['paid_at'],
                'method'           => $data['method'],
                'operation_number' => $data['operation_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            $company->update(['paid_until' => $periodTo]);
            $this->paid_until = $periodTo;

            return $payment;
        });
    }

    // ── Relaciones ──────────────────────────────────────────────

    /** Configuración de facturación electrónica (SUNAT) */
    public function sunatSetting(): HasOne
    {
        return $this->hasOne(CompanySunatSetting::class, 'company_id');
    }

    /** Sedes de la compañía */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'company_id');
    }

    /** Empleados de la compañía (todas las sedes) */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'company_id');
    }

    /** Categorías del catálogo */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'company_id');
    }

    /** Laboratorios del catálogo */
    public function laboratories(): HasMany
    {
        return $this->hasMany(Laboratory::class, 'company_id');
    }

    /** Productos del catálogo */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'company_id');
    }

    /** Pagos de la mensualidad del servicio */
    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'company_id');
    }

    /** Clientes de la compañía */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'company_id');
    }
}
