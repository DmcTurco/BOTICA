<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'products';
    protected $primaryKey = 'code';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'code',
        'company_id',
        'name',
        'description',
        'category_id',
        'laboratory_id',
        'active_ingredient',
        'purchase_price',
        'package_purchase_price',
        'units_per_package',
        'unit_sale_price',
        'package_sale_price',
        'unit_id',
        'taxed_product',
        'requires_recipe',
        'location',
        'status',
        'employee_id',
    ];

    protected $casts = [
        'purchase_price'         => 'decimal:2',
        'package_purchase_price' => 'decimal:2',
        'unit_sale_price'        => 'decimal:2',
        'package_sale_price'     => 'decimal:2',
        'taxed_product'          => 'boolean',
        'requires_recipe'        => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía dueña del catálogo */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Categoría del producto */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Laboratorio fabricante */
    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class, 'laboratory_id');
    }

    /** Unidad de medida principal */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /** Empleado que registró el producto */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Presentaciones disponibles (tableta, blíster, caja…) */
    public function presentations(): HasMany
    {
        return $this->hasMany(Presentation::class, 'product_code', 'code');
    }

    /** Stock por sede */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'product_code', 'code');
    }

    /** Movimientos de stock (kardex) */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_code', 'code');
    }

    // ── Helpers de stock (requieren branch_id) ───────────────────

    /** Stock actual en una sede específica */
    public function stockEnSede(int $branchId): float
    {
        return (float) $this->branchStocks()
            ->where('branch_id', $branchId)
            ->value('stock_actual') ?? 0;
    }

    /** ¿Hay stock disponible en una sede? */
    public function disponibleEnSede(int $branchId): bool
    {
        return $this->stockEnSede($branchId) > 0;
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Utilidad por unidad vendida */
    public function getUtilidadUnidadAttribute(): float
    {
        return (float) ($this->unit_sale_price - $this->purchase_price);
    }

    /** Utilidad por paquete vendido */
    public function getUtilidadPaqueteAttribute(): ?float
    {
        if ($this->package_sale_price && $this->package_purchase_price) {
            return (float) ($this->package_sale_price - $this->package_purchase_price);
        }
        return null;
    }

    // ── Aliases de relaciones (compatibilidad con vistas existentes) ──

    // /** @deprecated Usar category() */
    // public function categoria(): BelongsTo
    // {
    //     return $this->category();
    // }

    // /** @deprecated Usar laboratory() */
    // public function laboratorio(): BelongsTo
    // {
    //     return $this->laboratory();
    // }

    // /** @deprecated Usar unit() */
    // public function unidadMedida(): BelongsTo
    // {
    //     return $this->unit();
    // }

    // /** @deprecated Usar presentations() */
    // public function presentaciones(): HasMany
    // {
    //     return $this->presentations();
    // }
}
