<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_detail';

    protected $fillable = [
        'purchase_id',
        'product_code',
        'quantity',
        'unit_cost',
        'subtotal',
        'expiration_date',
        'batch',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'unit_cost'       => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compra padre */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /** Producto comprado */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }
}
