<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchAllocation extends Model
{
    protected $table = 'batch_allocations';

    protected $fillable = [
        'stock_batch_id',
        'reference_type',
        'reference_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /** Lote del que salieron las unidades */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}
