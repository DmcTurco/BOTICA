<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'company_id',
        'rate_date',
        'buy_rate',
        'sell_rate',
        'employee_id',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'buy_rate'  => 'float',
        'sell_rate' => 'float',
    ];

    /** Empleado que lo registró */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
