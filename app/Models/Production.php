<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $table = 'productions';

    protected $fillable = [
        'company_id',
        'branch_id',
        'formula_id',
        'employee_id',
        'batch',
        'multiplier',
        'quantity_produced',
        'produced_at',
        'expiration_date',
        'total_cost',
        'unit_cost',
        'product_code',
        'notes',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'multiplier'        => 'float',
        'quantity_produced' => 'float',
        'produced_at'       => 'date',
        'expiration_date'   => 'date',
        'voided_at'         => 'datetime',
        'total_cost'        => 'decimal:2',
        'unit_cost'         => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Sede donde se preparó */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Fórmula usada */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(Formula::class, 'formula_id')->withTrashed();
    }

    /** Empleado que registró la preparación */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Producto del catálogo que recibió el stock (null si no se vende en POS) */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }

    /** Insumos consumidos */
    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductionIngredient::class, 'production_id');
    }

    // ── Accessors ───────────────────────────────────────────────

    /** Estado del vencimiento para las vistas: vencido / por vencer (30 días) / vigente / sin fecha */
    public function getExpirationBadgeAttribute(): array
    {
        if (!$this->expiration_date) {
            return ['label' => 'Sin vencimiento', 'class' => 'bg-slate-100 text-slate-600'];
        }
        if ($this->expiration_date->isPast()) {
            return ['label' => 'Vencido', 'class' => 'bg-red-50 text-red-600'];
        }
        if ($this->expiration_date->lte(now()->addDays(30))) {
            return ['label' => 'Por vencer', 'class' => 'bg-amber-50 text-amber-700'];
        }
        return ['label' => 'Vigente', 'class' => 'bg-emerald-50 text-emerald-700'];
    }
}
