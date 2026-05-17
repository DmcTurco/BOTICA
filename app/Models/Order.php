<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'company_id',
        'branch_id',
        'cash_register_id',
        'employee_id',
        'client_id',
        'customer_name',
        'document_type_id',
        'customer_document',
        'voucher_type',
        'voucher_number',
        'payment_type',
        'operation_number',
        'subtotal',
        'igv',
        'total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv'      => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede donde se realizó la venta */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Caja en la que se registró */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    /** Empleado que realizó la venta */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Cliente registrado (null = venta anónima) */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /** Tipo de documento del cliente */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /** Ítems de la orden */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
