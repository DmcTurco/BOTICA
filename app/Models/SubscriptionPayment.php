<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de la mensualidad del servicio registrado por el administrador.
 */
class SubscriptionPayment extends Model
{
    protected $fillable = [
        'company_id',
        'admin_id',
        'amount',
        'months',
        'period_from',
        'period_to',
        'paid_at',
        'method',
        'operation_number',
        'notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'months'      => 'integer',
        'period_from' => 'date',
        'period_to'   => 'date',
        'paid_at'     => 'date',
    ];

    // Medios de pago aceptados
    const METHODS = [
        'yape'     => 'Yape',
        'plin'     => 'Plin',
        'transfer' => 'Transferencia',
        'cash'     => 'Efectivo',
        'other'    => 'Otro',
    ];

    /** Empresa que pagó */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Administrador que registró el pago */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
