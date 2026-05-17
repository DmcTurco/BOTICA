<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'icon',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía dueña de esta categoría */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Productos de esta categoría */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo categorías activas */
    public function scopeActivas($query)
    {
        return $query->where('status', 1);
    }

    // ── Alias (compatibilidad con vistas existentes) ─────────────

    /** @deprecated Usar products() */
    public function productos(): HasMany
    {
        return $this->products();
    }
}
