<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Employee;
use Illuminate\Http\Request;

class CashClosureController extends Controller
{
    /**
     * Historial de cajas de la sede (por defecto las del mes) con diferencias de cierre.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $desde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        $query = CashRegister::with('employee:id,name')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('register_date', '>=', $desde)
            ->whereDate('register_date', '<=', $hasta);

        // Estado: abiertas o cerradas
        if ($request->input('estado') === 'cerradas') {
            $query->where('status', 0);
        } elseif ($request->input('estado') === 'abiertas') {
            $query->where('status', 1);
        }

        if ($request->filled('empleado')) {
            $query->where('employee_id', $request->empleado);
        }

        // Solo con diferencia (faltante o sobrante)
        if ($request->boolean('con_diferencia')) {
            $query->where('status', 0)->whereRaw('COALESCE(difference, 0) <> 0');
        }

        $registers = $query->orderByDesc('register_date')->orderByDesc('id')->paginate(15)->withQueryString();

        // Totales de ventas de la página (una consulta por caja es aceptable con 15 filas)
        $registers->getCollection()->each(function (CashRegister $r) {
            $r->sales_total = $r->totalOrders();
        });

        $employees = Employee::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('name')->get(['id', 'name']);

        return view('employee.pages.cash-closures.index', compact('registers', 'employees', 'desde', 'hasta'));
    }

    /**
     * Detalle de una caja: ventas por forma de pago, gastos, ingresos y diferencia.
     */
    public function show(CashRegister $cashRegister)
    {
        $this->authorizeRegister($cashRegister);

        $cashRegister->load(['employee:id,name', 'approvedBy:id,name', 'movements.employee:id,name']);

        return view('employee.pages.cash-closures.show', [
            'caja'          => $cashRegister,
            'paymentTotals' => $cashRegister->totalsByPaymentType(),
            'paymentLabels' => CashRegister::PAYMENT_TYPE_LABELS,
            'ordersCount'   => $cashRegister->orders()->where('status', 1)->count(),
        ]);
    }

    /**
     * Hoja imprimible con el movimiento de caja de un día: todas las cajas de la sede de esa fecha.
     */
    public function print(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $date = $request->input('fecha') ?: today()->toDateString();

        $registers = CashRegister::with(['employee:id,name', 'movements' => fn ($q) => $q->where('status', 1)->orderBy('id')])
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('register_date', $date)
            ->orderBy('id')
            ->get();

        $rows = $registers->map(fn (CashRegister $r) => [
            'register' => $r,
            'payments' => $r->totalsByPaymentType(),
            'income'   => $r->otherIncome(),
            'expenses' => $r->expenses(),
            'expected' => $r->expectedCash(),
        ]);

        return view('employee.pages.cash-closures.print', [
            'date'   => $date,
            'rows'   => $rows,
            'branch' => $employee->branch,
            'labels' => CashRegister::PAYMENT_TYPE_LABELS,
        ]);
    }

    /** Solo cajas de la misma compañía y sede */
    private function authorizeRegister(CashRegister $cashRegister): void
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->company_id !== $employee->company_id || $cashRegister->branch_id !== $employee->branch_id,
            403
        );
    }
}
