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
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
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
}
