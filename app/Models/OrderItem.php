<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_detail';

    protected $fillable = [
        'order_id',
        'product_code',
        'product_name',
        'presentation_id',
        'units_per_item',
        'unit_price',
        'quantity',
        'discount_amount',
        'subtotal',
        'igv_affectation',
        'igv_amount',
        'unit_code',
    ];

    protected $casts = [
        'unit_price'     => 'decimal:2',
        'quantity'       => 'decimal:2',
        'units_per_item' => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'igv_amount'     => 'decimal:2',
    ];

    // Relación con el pedido al que pertenece este ítem
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Relación con el producto
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    // Presentación vendida (null = unidad base del producto)
    public function presentation()
    {
        return $this->belongsTo(Presentation::class, 'presentation_id')->withTrashed();
    }

    /**
     * Unidades base que mueve este ítem en el stock: cantidad × unidades de la presentación.
     * Ej: 2 blísteres de 10 tabletas = 20. Es lo que se descuenta o se devuelve al stock.
     */
    public function getBaseQuantityAttribute(): float
    {
        return round((float) $this->quantity * (float) ($this->units_per_item ?: 1), 2);
    }
}
