<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;           // solo tiene created_at: el registro no se modifica

    protected $table = 'audit_logs';

    /** Acciones que se registran, agrupadas para filtrar en la pantalla */
    const GROUPS = [
        'Compras y stock' => ['purchase.void', 'transfer.void', 'production.void', 'stock.adjust', 'batch.write_off'],
        'Ventas y caja'   => ['sale.discount', 'sale.credit', 'credit_note.issue', 'cash.approve', 'cash.reject', 'receivable.pay', 'payable.pay'],
        'Precios'         => ['price.update', 'price.bulk'],
        'Configuración'   => ['series.update', 'commission.update', 'local.update', 'employee.create', 'employee.update', 'employee.delete'],
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'employee_name',
        'action',
        'subject',
        'description',
        'details',
        'ip',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    /** Empleado que hizo la acción */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Registra una acción del empleado autenticado. Nunca debe romper la operación principal:
     * si no se puede escribir en la bitácora, se ignora el error.
     *
     * @param  array<string, mixed>  $details  datos útiles (montos, motivo, valores anterior y nuevo)
     */
    public static function record(string $action, string $description, ?string $subject = null, array $details = []): void
    {
        try {
            $employee = auth()->guard('employee')->user();

            static::create([
                'company_id'    => $employee?->company_id ?? 0,
                'branch_id'     => $employee?->branch_id,
                'employee_id'   => $employee?->id,
                'employee_name' => $employee?->name,
                'action'        => $action,
                'subject'       => $subject,
                'description'   => mb_substr($description, 0, 255),
                'details'       => $details ?: null,
                'ip'            => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
