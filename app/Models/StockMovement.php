<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_code',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'balance',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'quantity'  => 'integer',
        'balance'   => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede donde ocurrió el movimiento */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Producto afectado */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Badge de tipo para las vistas */
    public function getTipoBadgeAttribute(): array
    {
        return match ($this->type) {
            'entrada' => ['label' => 'Entrada', 'class' => 'bg-emerald-50 text-emerald-700'],
            'salida'  => ['label' => 'Salida',  'class' => 'bg-red-50 text-red-600'],
            default   => ['label' => 'Ajuste',  'class' => 'bg-amber-50 text-amber-700'],
        };
    }

    /** Etiqueta del origen del movimiento */
    public function getReferenciaLabelAttribute(): string
    {
        return match ($this->reference_type) {
            'purchase' => 'Compra #' . $this->reference_id,
            'order'    => 'Venta #'  . $this->reference_id,
            'manual'   => 'Ajuste manual',
            default    => '—',
        };
    }
}
