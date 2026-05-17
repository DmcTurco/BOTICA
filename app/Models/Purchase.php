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
        'branch_id',
        'employee_id',
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

    const DOCUMENT_TYPES = [
        1 => 'Boleta',
        2 => 'Factura',
        3 => 'Nota de ingreso',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede que recibió el stock */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Empleado que registró la compra */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Líneas de detalle */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Etiqueta legible del tipo de documento */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Desconocido';
    }
}
