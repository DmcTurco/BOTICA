<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'customer_name',
        'customer_document',
        'voucher_type',
        'voucher_number',
        'payment_type',
        'operation_number',
        'subtotal',
        'igv',
        'total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv'      => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }
}
