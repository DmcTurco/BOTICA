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
        'product_id',
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
        'quantity'  => 'float',
        'balance'   => 'float',
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
        return $this->belongsTo(Product::class)->withTrashed();
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

    /**
     * Cantidad que entra: las entradas y los ajustes positivos (inventario inicial, carga de datos).
     * Los ajustes guardan la cantidad con signo; entradas y salidas, en valor absoluto.
     */
    public function getInQuantityAttribute(): float
    {
        return match ($this->type) {
            'entrada' => (float) $this->quantity,
            'ajuste'  => max((float) $this->quantity, 0),
            default   => 0,
        };
    }

    /** Cantidad que sale: las salidas y los ajustes negativos (en valor absoluto) */
    public function getOutQuantityAttribute(): float
    {
        return match ($this->type) {
            'salida' => (float) $this->quantity,
            'ajuste' => abs(min((float) $this->quantity, 0)),
            default  => 0,
        };
    }

    /** Etiqueta del origen del movimiento */
    public function getReferenciaLabelAttribute(): string
    {
        return match ($this->reference_type) {
            'purchase' => 'Compra #' . $this->reference_id,
            'order'    => 'Venta #'  . $this->reference_id,
            'manual'   => 'Ajuste manual',
            'initial'  => 'Inventario inicial',
            'order_edit'          => 'Venta editada #' . $this->reference_id,
            'order_edit_reversal' => 'Reversión por edición #' . $this->reference_id,
            'production' => 'Preparación #' . $this->reference_id,
            'production_void' => 'Anulación de preparación #' . $this->reference_id,
            'purchase_void'   => 'Anulación de compra #' . $this->reference_id,
            'transfer'        => 'Traspaso #' . $this->reference_id,
            'transfer_void'   => 'Anulación de traspaso #' . $this->reference_id,
            'credit_note' => 'Anulación (NC #' . $this->reference_id . ')',
            default    => '—',
        };
    }
}
