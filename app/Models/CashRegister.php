<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CashRegister extends Model
{
    protected $table = 'cash_registers';

    // ── Constantes de estado de aprobación ──────────────────────
    const APPROVAL_NORMAL   = 0; // Caja del día actual — aprobada automáticamente
    const APPROVAL_PENDING  = 1; // Caja histórica — pendiente de validación por branch_admin
    const APPROVAL_APPROVED = 2; // Caja histórica — aprobada por branch_admin
    const APPROVAL_REJECTED = 3; // Caja histórica — rechazada por branch_admin

    const APPROVAL_LABELS = [
        self::APPROVAL_NORMAL   => 'Normal',
        self::APPROVAL_PENDING  => 'Pendiente',
        self::APPROVAL_APPROVED => 'Aprobada',
        self::APPROVAL_REJECTED => 'Rechazada',
    ];

    // ── Formas de pago (orders.payment_type) ────────────────────
    const PAYMENT_CASH = 1;

    const PAYMENT_TYPE_LABELS = [
        1 => 'Efectivo',
        2 => 'Tarjeta',
        3 => 'Transferencia',
        4 => 'Yape',
        5 => 'Crédito',
    ];

    // Medios con los que se puede pagar un abono, una compra o una deuda (el crédito no es un medio de pago)
    const PAYMENT_METHODS = [
        1 => 'Efectivo',
        2 => 'Tarjeta',
        3 => 'Transferencia',
        4 => 'Yape',
    ];

    const PAYMENT_CREDIT = 5;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'opening_amount',
        'opening_denominations',
        'closing_amount',
        'closing_denominations',
        'expected_amount',
        'difference',
        'status',
        'register_date',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount'        => 'decimal:2',
        'opening_denominations' => 'array',
        'closing_amount'        => 'decimal:2',
        'closing_denominations' => 'array',
        'expected_amount'       => 'decimal:2',
        'difference'            => 'decimal:2',
        'register_date'         => 'date',
        'opened_at'             => 'datetime',
        'closed_at'             => 'datetime',
        'approved_at'           => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Branch_admin que aprobó o rechazó la caja */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'cash_register_id');
    }

    /** Gastos y otros ingresos registrados en esta caja */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cash_register_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Cajas con status abierta */
    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Caja normal abierta del empleado (para el POS).
     * No filtra por fecha: si no se cerró al terminar el día, sigue abierta
     * al cambiar de día. Las cajas históricas (pendientes de validación) no cuentan.
     */
    public function scopeCurrentOpen($query, int $employeeId)
    {
        return $query->where('status', 1)
                     ->where('employee_id', $employeeId)
                     ->where('approval_status', self::APPROVAL_NORMAL);
    }

    /** Cajas históricas (fecha anterior a hoy, abiertas a propósito para validación) */
    public function scopeHistorical($query)
    {
        return $query->whereDate('register_date', '<', Carbon::today())
                     ->where('approval_status', '!=', self::APPROVAL_NORMAL);
    }

    /** Cajas históricas pendientes de aprobación */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Es una caja histórica? (fecha anterior a hoy y no es una caja normal que quedó abierta) */
    public function isHistorical(): bool
    {
        return $this->approval_status !== self::APPROVAL_NORMAL
            && Carbon::parse($this->register_date)->lt(Carbon::today());
    }

    /** ¿Está pendiente de validación? */
    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /** ¿Fue aprobada (o es normal)? */
    public function isApproved(): bool
    {
        return in_array($this->approval_status, [self::APPROVAL_NORMAL, self::APPROVAL_APPROVED]);
    }

    /** ¿Fue rechazada? */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /** ¿Se pueden editar sus órdenes? Solo si está abierta y pendiente */
    public function isEditable(): bool
    {
        return $this->status === 1 && $this->isPending();
    }

    /** Etiqueta del estado de aprobación */
    public function approvalLabel(): string
    {
        return self::APPROVAL_LABELS[$this->approval_status] ?? 'Desconocido';
    }

    /** Total facturado en las órdenes activas de esta caja (todas las formas de pago) */
    public function totalOrders(): float
    {
        return (float) $this->orders()->where('status', 1)->sum('total');
    }

    /**
     * Ventas activas de la caja agrupadas por forma de pago.
     * Devuelve [payment_type => total] con todas las formas siempre presentes.
     */
    public function totalsByPaymentType(): array
    {
        $totals = array_fill_keys(array_keys(self::PAYMENT_TYPE_LABELS), 0.0);

        $rows = $this->orders()
            ->where('status', 1)
            ->selectRaw('payment_type, SUM(total) as total')
            ->groupBy('payment_type')
            ->get();

        foreach ($rows as $row) {
            $type = (int) $row->payment_type;
            if (array_key_exists($type, $totals)) {
                $totals[$type] = round((float) $row->total, 2);
            }
        }

        return $totals;
    }

    /** Total vendido en efectivo en esta caja */
    public function totalCash(): float
    {
        return $this->totalsByPaymentType()[self::PAYMENT_CASH];
    }

    /** Otros ingresos en efectivo registrados en la caja (no son ventas) */
    public function otherIncome(): float
    {
        return (float) $this->movements()->active()->where('type', CashMovement::INCOME)->sum('amount');
    }

    /** Gastos pagados con efectivo de la caja */
    public function expenses(): float
    {
        return (float) $this->movements()->active()->where('type', CashMovement::EXPENSE)->sum('amount');
    }

    /** Abonos de clientes (fiado) cobrados en efectivo en esta caja */
    public function creditCollections(): float
    {
        return (float) CreditPayment::where('cash_register_id', $this->id)->where('method', self::PAYMENT_CASH)->sum('amount');
    }

    /** Efectivo que debería haber en el cajón: apertura + ventas en efectivo + abonos + otros ingresos − gastos */
    public function expectedCash(): float
    {
        return round((float) $this->opening_amount + $this->totalCash() + $this->creditCollections() + $this->otherIncome() - $this->expenses(), 2);
    }
}
