<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $table = 'credit_payments';

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_id',
        'client_id',
        'cash_register_id',
        'employee_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'batch',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    /** Venta a crédito abonada */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Cliente que pagó */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /** Empleado que cobró */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
