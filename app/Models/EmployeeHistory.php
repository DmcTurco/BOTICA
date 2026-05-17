<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHistory extends Model
{
    protected $table = 'employee_histories';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'role_id',
        'started_at',
        'ended_at',
        'reason',
        'authorized_by',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    // ── Constantes de motivo ─────────────────────────────────────

    const REASON_INGRESO       = 'ingreso';
    const REASON_TRANSFERENCIA = 'transferencia';
    const REASON_PROMOCION     = 'promocion';
    const REASON_AJUSTE        = 'ajuste';

    // ── Relaciones ──────────────────────────────────────────────

    /** Empleado al que pertenece este registro */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Sede en la que estuvo el empleado en este período */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Rol que tenía el empleado en este período */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Empleado que autorizó el cambio */
    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'authorized_by');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** ¿Es el registro activo (sin fecha de cierre)? */
    public function getEsActualAttribute(): bool
    {
        return $this->ended_at === null;
    }

    /** Etiqueta legible del motivo */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            self::REASON_INGRESO       => 'Ingreso',
            self::REASON_TRANSFERENCIA => 'Transferencia',
            self::REASON_PROMOCION     => 'Promoción',
            self::REASON_AJUSTE        => 'Ajuste',
            default                    => $this->reason ?? '—',
        };
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo el registro activo (ended_at null) */
    public function scopeActual($query)
    {
        return $query->whereNull('ended_at');
    }
}
