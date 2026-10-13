<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    protected $table = 'cash_movements';

    const INCOME  = 'income';
    const EXPENSE = 'expense';

    const TYPE_LABELS = [
        self::INCOME  => 'Otro ingreso',
        self::EXPENSE => 'Gasto',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'cash_register_id',
        'employee_id',
        'type',
        'concept',
        'amount',
        'notes',
        'status',
        'void_reason',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status'    => 'integer',
        'voided_at' => 'datetime',
    ];

    /** Caja en la que se registró */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    /** Empleado que lo registró */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Empleado que lo anuló */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'voided_by');
    }

    /** Solo movimientos vigentes (no anulados) */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
