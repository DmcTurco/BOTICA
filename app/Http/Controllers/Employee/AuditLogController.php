<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Bitácora de la empresa: acciones sensibles con quién, cuándo y qué cambió.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $desde = $request->input('fecha_desde') ?: now()->subDays(7)->toDateString();
        $hasta = $request->input('fecha_hasta') ?: now()->toDateString();

        $query = AuditLog::where('company_id', $employee->company_id)
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);

        if ($request->filled('grupo') && isset(AuditLog::GROUPS[$request->grupo])) {
            $query->whereIn('action', AuditLog::GROUPS[$request->grupo]);
        }
        if ($request->filled('empleado')) {
            $query->where('employee_id', $request->empleado);
        }
        if ($request->filled('buscar')) {
            $query->where(fn ($q) => $q->where('description', 'like', '%' . $request->buscar . '%')
                ->orWhere('subject', 'like', '%' . $request->buscar . '%'));
        }

        return view('employee.pages.audit.index', [
            'logs'      => $query->orderByDesc('id')->paginate(25)->withQueryString(),
            'desde'     => $desde,
            'hasta'     => $hasta,
            'groups'    => array_keys(AuditLog::GROUPS),
            'employees' => Employee::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
