<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'document_type_id',
        'document_number',
        'phone',
        'email',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece el cliente */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Tipo de documento de identidad */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /** Órdenes realizadas por este cliente */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo clientes activos */
    public function scopeActivos($query)
    {
        return $query->where('status', 1);
    }
}
