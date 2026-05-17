<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// /**
//  * @deprecated El archivo debe llamarse Laboratory.php — renombrar cuando sea posible.
//  */
class Laboratory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laboratories';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'country',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía dueña de este laboratorio */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Productos fabricados por este laboratorio */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'laboratory_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo laboratorios activos */
    public function scopeActivos($query)
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
