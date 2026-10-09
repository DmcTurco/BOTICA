<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionIngredient extends Model
{
    protected $table = 'production_ingredients';

    protected $fillable = [
        'production_id',
        'product_code',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'quantity'  => 'float',
        'unit_cost' => 'decimal:2',
        'subtotal'  => 'decimal:2',
    ];

    /** Preparación a la que pertenece */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    /** Insumo consumido */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }
}
