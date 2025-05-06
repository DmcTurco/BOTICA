<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'presentations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'product_code',
        'unit_id',
        'equivalent_amount',
        'sale_price',
        'main_presentation',
        'status'
    ];

    protected $casts = [
        'equivalent_amount' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'main_presentation' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
