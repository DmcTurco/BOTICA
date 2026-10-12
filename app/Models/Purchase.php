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
        'supplier_id',
        'payment_condition',
        'due_date',
        'paid_amount',
        'purchase_order_id',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
        'voided_at',
        'void_reason',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'due_date'     => 'date',
        'paid_amount'  => 'decimal:2',
        'voided_at'    => 'datetime',
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

    /** Proveedor registrado (puede ser null en compras antiguas) */
    public function supplierRecord(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    /** Pagos hechos al proveedor por esta compra */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'purchase_id');
    }

    /** Líneas de detalle */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Lo que aún se debe al proveedor (0 si es al contado o está anulada) */
    public function getBalanceAttribute(): float
    {
        if ($this->payment_condition !== 'credit' || (int) $this->status === 0) {
            return 0.0;
        }

        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    /** ¿Tiene deuda vencida? */
    public function isOverdue(): bool
    {
        return $this->balance > 0 && $this->due_date !== null && $this->due_date->lt(today());
    }

    /** Etiqueta legible del tipo de documento */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Desconocido';
    }
}
