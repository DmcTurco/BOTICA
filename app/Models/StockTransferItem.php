<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'product_code',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /** Traspaso al que pertenece */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    /** Producto traspasado */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }
}
