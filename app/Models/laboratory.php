<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class laboratory extends Model
{
    use HasFactory;

    protected $table = 'laboratories';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'status'
    ];

    public function productos()
    {
        return $this->hasMany(Product::class, 'laboratory_id');
    }
}
