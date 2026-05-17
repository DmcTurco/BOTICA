<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStock extends Model
{
    protected $table = 'branch_stock';

    protected $fillable = [
        'branch_id',
        'product_code',
        'stock_actual',
        'stock_minimum',
        'stock_maximum',
    ];

    protected $casts = [
        'stock_actual'  => 'decimal:2',
        'stock_minimum' => 'integer',
        'stock_maximum' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Sede a la que pertenece este stock */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Producto del catálogo */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** ¿Hay unidades disponibles en esta sede? */
    public function getDisponibleAttribute(): bool
    {
        return $this->stock_actual > 0;
    }

    /** ¿El stock está por debajo del mínimo? */
    public function getBajoStockAttribute(): bool
    {
        if ($this->stock_minimum === null) {
            return false;
        }
        return $this->stock_actual <= $this->stock_minimum;
    }

    /** Valor del inventario en esta sede (stock × precio de compra) */
    public function getValorInventarioAttribute(): float
    {
        return (float) ($this->stock_actual * $this->product?->purchase_price);
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo registros con stock disponible */
    public function scopeDisponible($query)
    {
        return $query->where('stock_actual', '>', 0);
    }

    /** Solo registros bajo el mínimo */
    public function scopeBajoMinimo($query)
    {
        return $query->whereNotNull('stock_minimum')
                     ->whereColumn('stock_actual', '<=', 'stock_minimum');
    }
}
