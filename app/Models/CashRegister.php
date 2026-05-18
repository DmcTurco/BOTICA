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

    // ── Scopes ──────────────────────────────────────────────────

    /** Cajas con status abierta */
    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    /** Caja del día actual del empleado (para el POS normal) */
    public function scopeTodayOpen($query, int $employeeId)
    {
        return $query->where('status', 1)
                     ->where('employee_id', $employeeId)
                     ->whereDate('register_date', Carbon::today());
    }

    /** Cajas históricas (register_date < hoy) */
    public function scopeHistorical($query)
    {
        return $query->whereDate('register_date', '<', Carbon::today());
    }

    /** Cajas históricas pendientes de aprobación */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Es una caja histórica? (fecha anterior a hoy) */
    public function isHistorical(): bool
    {
        return Carbon::parse($this->register_date)->lt(Carbon::today());
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

    /** Total facturado en las órdenes activas de esta caja */
    public function totalOrders(): float
    {
        return (float) $this->orders()->where('status', 1)->sum('total');
    }
}
