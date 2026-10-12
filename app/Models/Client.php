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
        'credit_limit',
        'status',
    ];

    protected $casts = [
        'status'       => 'integer',
        'credit_limit' => 'decimal:2',
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

    /** Lo que el cliente debe hoy por ventas a crédito vigentes */
    public function creditDebt(): float
    {
        return round((float) $this->orders()->where('status', 1)->where('credit_balance', '>', 0)->sum('credit_balance'), 2);
    }

    /** Crédito que aún puede usar (tope − deuda) */
    public function creditAvailable(): float
    {
        return max(0, round((float) $this->credit_limit - $this->creditDebt(), 2));
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
