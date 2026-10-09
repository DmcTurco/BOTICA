<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'company_id',
        'from_branch_id',
        'to_branch_id',
        'employee_id',
        'notes',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'status'    => 'integer',
        'voided_at' => 'datetime',
    ];

    /** Sede que despacha */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /** Sede que recibe */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /** Empleado que registró el traspaso */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Productos traspasados */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    /** ¿Sigue vigente? */
    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
