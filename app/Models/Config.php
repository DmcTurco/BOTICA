<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Config extends Model
{
    protected $fillable = ['configurable_type', 'configurable_id', 'setting'];

    protected $casts = [
        'setting' => 'array',
    ];

    /**
     * Entidad dueña de esta configuración (Branch, Company, etc.)
     */
    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }
}
