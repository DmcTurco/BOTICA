<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $table = 'sale_details';

    protected $fillable = [
        'sale_id',
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

    public function venta()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }
}
