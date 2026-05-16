<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'cash_register_id',
        'customer_name',
        'document_type_id',
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

    // Relación con los ítems del pedido
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // Relación con la caja en la que se registró
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    // Tipo de documento del cliente
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
