<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulaIngredient extends Model
{
    protected $table = 'formula_ingredients';

    protected $fillable = [
        'formula_id',
        'product_code',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /** Fórmula a la que pertenece */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(Formula::class, 'formula_id');
    }

    /** Insumo (producto del catálogo) */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }
}
