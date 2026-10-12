<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'company_id',
        'ruc',
        'name',
        'contact',
        'phone',
        'email',
        'address',
        'notes',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /** Compras hechas a este proveedor */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    /** Órdenes de compra enviadas a este proveedor */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }
}
