<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceChange extends Model
{
    protected $table = 'price_changes';

    const FIELD_LABELS = [
        'purchase_price'  => 'Precio de compra',
        'unit_sale_price' => 'Precio de venta',
    ];

    protected $fillable = [
        'company_id',
        'product_code',
        'field',
        'old_value',
        'new_value',
        'employee_id',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
    ];

    /** Producto cuyo precio cambió */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }

    /** Empleado que hizo el cambio */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
