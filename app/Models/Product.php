<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'came',
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
        'stock_actual',
        'stock_minimum',
        'stock_maximum',
        'taxed_product',
        'requires_recipe',
        'expiration_date',
        'location',
        'status',
    ];

    // Accessor para usar $producto->nombre en vistas y controlador
    public function getNombreAttribute(): string
    {
        return $this->came ?? '';
    }

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'package_purchase_price' => 'decimal:2',
        'unit_sale_price' => 'decimal:2',
        'package_sale_price' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'taxed_product' => 'boolean',
        'requires_recipe' => 'boolean',
        'expiration_date' => 'date',
    ];

    public function categoria()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratory::class, 'laboratory_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentation::class, 'product_code');
    }

    // Calcular utilidad por unidad
    public function getUtilidadUnidadAttribute()
    {
        return $this->unit_sale_price - $this->purchase_price;
    }

    // Calcular utilidad por paquete
    public function getUtilidadPaqueteAttribute()
    {
        if ($this->package_sale_price && $this->package_purchase_price) {
            return $this->package_sale_price - $this->package_purchase_price;
        }
        return null;
    }

    // Valor de inventario
    public function getValorInventarioAttribute()
    {
        return $this->stock_actual * $this->purchase_price;
    }

    // Calcular si está en stock
    public function getDisponibleAttribute()
    {
        return $this->stock_actual > 0;
    }

    // Calcular si está por debajo del stock mínimo
    public function getBajoStockAttribute()
    {
        if ($this->stock_minimum === null) {
            return false;
        }
        return $this->stock_actual <= $this->stock_minimum;
    }
}
