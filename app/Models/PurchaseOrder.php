<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    const PENDING   = 'pending';
    const RECEIVED  = 'received';
    const CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::PENDING   => 'Pendiente',
        self::RECEIVED  => 'Recibida',
        self::CANCELLED => 'Anulada',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'employee_id',
        'number',
        'status',
        'expected_date',
        'notes',
        'total',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'total'         => 'decimal:2',
    ];

    /** Proveedor al que se le pide */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    /** Sede que recibirá la mercadería */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Empleado que la emitió */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Productos pedidos */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Siguiente número correlativo de la compañía (OC-0001, OC-0002...).
     */
    public static function nextNumber(int $companyId): string
    {
        $last = static::where('company_id', $companyId)->orderByDesc('id')->value('number');

        return 'OC-' . str_pad((string) (($last ? (int) substr($last, 3) : 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Etiqueta del estado */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
