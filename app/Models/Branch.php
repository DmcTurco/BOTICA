<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece la sede */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Empleados cuya sede principal es esta */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'branch_id');
    }

    /** Cajas registradoras de esta sede */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'branch_id');
    }

    /** Órdenes/ventas realizadas en esta sede */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'branch_id');
    }

    /** Compras realizadas en esta sede */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'branch_id');
    }

    /** Movimientos de stock de esta sede */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'branch_id');
    }

    /** Stock de productos en esta sede */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'branch_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo sedes activas */
    public function scopeActivas($query)
    {
        return $query->where('status', 1);
    }
}
