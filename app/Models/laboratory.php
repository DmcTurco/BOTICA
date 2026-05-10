<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laboratory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laboratories';

    protected $fillable = [
        'name',
        'description',
        'country',
        'phone',
        'email',
        'status',
    ];

    public function productos()
    {
        return $this->hasMany(Product::class, 'laboratory_id');
    }
}
