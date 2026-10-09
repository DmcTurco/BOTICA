<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LostSale extends Model
{
    protected $table = 'lost_sales';

    const REASONS = [
        'no_stock'    => 'Sin stock',
        'not_carried' => 'No lo manejamos',
        'price'       => 'Precio',
        'other'       => 'Otro',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'product_code',
        'product_name',
        'quantity',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /** Empleado que lo registró */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Etiqueta del motivo */
    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
