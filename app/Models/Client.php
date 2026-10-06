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

    /** Cliente predeterminado de las ventas al público general (sin identificar) */
    const PUBLIC_NAME     = 'CLIENTE PÚBLICO';
    const PUBLIC_DOCUMENT = '00000000';

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

    /**
     * Cliente público de la compañía (DNI 00000000). Si aún no existe se crea, así toda
     * compañía lo tiene sin depender del seeder.
     */
    public static function publicFor(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId, 'document_number' => self::PUBLIC_DOCUMENT],
            [
                'code'             => DocumentSeries::siguiente(DocumentSeries::CLIENTE),
                'name'             => self::PUBLIC_NAME,
                'document_type_id' => DocumentType::DNI,
                'status'           => 1,
            ]
        );
    }

    /** ¿Es el cliente público (venta sin identificar)? */
    public function isPublic(): bool
    {
        return $this->document_number === self::PUBLIC_DOCUMENT;
    }

    /** Solo clientes activos */
    public function scopeActivos($query)
    {
        return $query->where('status', 1);
    }
}
