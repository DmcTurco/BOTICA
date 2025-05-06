<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'units';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'abbreviation',
        'status'
    ];

    public function productos()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentation::class, 'unit_id');
    }
}
