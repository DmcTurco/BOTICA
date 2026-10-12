<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $table = 'stock_batches';

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_code',
        'batch',
        'expiration_date',
        'quantity_initial',
        'quantity_remaining',
        'unit_cost',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'expiration_date'    => 'date',
        'quantity_initial'   => 'float',
        'quantity_remaining' => 'float',
        'unit_cost'          => 'float',
    ];

    /** Producto del lote */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }

    /** Sede donde está el lote */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** ¿Ya venció? (vence el día indicado: ese día aún se puede vender) */
    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->lt(today());
    }

    /** Días que faltan para vencer (negativo si ya venció; null si no vence) */
    public function daysToExpire(): ?int
    {
        return $this->expiration_date ? (int) today()->diffInDays($this->expiration_date, false) : null;
    }
}
