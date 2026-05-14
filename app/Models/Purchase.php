<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';

    protected $fillable = [
        'company_id',
        'document_type',
        'document_number',
        'supplier',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'subtotal'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /** Tipos de documento disponibles */
    const DOCUMENT_TYPES = [
        1 => 'Boleta',
        2 => 'Factura',
        3 => 'Nota de ingreso',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Líneas de detalle de esta compra */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id');
    }

    /** Empresa que registró la compra */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Etiqueta legible del tipo de documento */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Desconocido';
    }
}
