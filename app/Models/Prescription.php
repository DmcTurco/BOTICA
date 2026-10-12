<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    protected $table = 'prescriptions';

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_id',
        'employee_id',
        'patient_name',
        'patient_document',
        'doctor_name',
        'doctor_license',
        'establishment',
        'prescription_number',
        'prescription_date',
    ];

    protected $casts = [
        'prescription_date' => 'date',
    ];

    /** Venta en la que se despachó */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Empleado que despachó */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
