<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $table = 'cash_registers';

    protected $fillable = [
        'company_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'status',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount'  => 'decimal:2',
        'closing_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference'      => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    // Órdenes registradas en esta caja
    public function orders()
    {
        return $this->hasMany(Order::class, 'cash_register_id');
    }

    // Empresa propietaria de la caja
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Scope para obtener la caja abierta actualmente
    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    // Calcula el total facturado sumando las órdenes activas de esta caja
    public function calcularTotalOrdenes(): float
    {
        return (float) $this->orders()->where('status', 1)->sum('total');
    }
}
