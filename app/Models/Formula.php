<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formula extends Model
{
    use SoftDeletes;

    protected $table = 'formulas';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'pharmaceutical_form',
        'description',
        'yield_quantity',
        'yield_unit_id',
        'procedure',
        'storage',
        'shelf_life_days',
        'sell_in_pos',
        'product_code',
        'status',
        'employee_id',
    ];

    protected $casts = [
        'yield_quantity'  => 'float',
        'shelf_life_days' => 'integer',
        'sell_in_pos'     => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía dueña de la fórmula */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Unidad del producto final */
    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'yield_unit_id');
    }

    /** Producto del catálogo que recibe el stock (solo si se vende en el POS) */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    /** Empleado que registró la fórmula */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Insumos de la fórmula */
    public function ingredients(): HasMany
    {
        return $this->hasMany(FormulaIngredient::class, 'formula_id');
    }

    /** Preparaciones (lotes) hechas con esta fórmula */
    public function productions(): HasMany
    {
        return $this->hasMany(Production::class, 'formula_id');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Genera el siguiente código correlativo (FM-0001, FM-0002...) de la compañía.
     * Incluye las fórmulas eliminadas para no repetir códigos.
     */
    public static function nextCode(int $companyId): string
    {
        $last = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'FM-%')
            ->orderByDesc('id')
            ->value('code');

        $number = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'FM-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /** Costo estimado de la fórmula base según el precio de compra actual de los insumos */
    public function estimatedCost(): float
    {
        return (float) $this->ingredients->sum(
            fn ($i) => $i->quantity * ($i->product?->purchase_price ?? 0)
        );
    }
}
