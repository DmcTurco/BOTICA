<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CashMovementRequest;
use App\Http\Requests\Company\VoidCashMovementRequest;
use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashMovementController extends Controller
{
    /**
     * Gastos y otros ingresos de la sede en un rango de fechas (por defecto hoy),
     * con el resumen de la caja abierta del empleado.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        $desde = $request->input('fecha_desde') ?: today()->toDateString();
        $hasta = $request->input('fecha_hasta') ?: today()->toDateString();

        $movements = CashMovement::with(['employee:id,name', 'cashRegister:id,status'])
            ->where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('employee.pages.cash-movements.index', [
            'caja'       => $caja,
            'movements'  => $movements,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'myRegister' => $caja?->id,
        ]);
    }

    /**
     * Formulario para registrar un gasto u otro ingreso en la caja abierta del empleado.
     * Sin caja abierta no hay dónde registrarlo: vuelve al listado con el aviso.
     */
    public function create()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.cash-movements.index')
                ->with('error', 'Abre tu caja antes de registrar movimientos.');
        }

        return view('employee.pages.cash-movements.form', compact('caja'));
    }

    /**
     * Registra el movimiento en la caja abierta del empleado.
     * Un gasto no puede superar el efectivo que hay en el cajón.
     */
    public function store(CashMovementRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        // Se bloquea la caja para que dos gastos simultáneos no sobrepasen el efectivo disponible
        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->lockForUpdate()->first();

        if (!$caja) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Abre tu caja antes de registrar movimientos.');
        }

        $amount = round((float) $request->amount, 2);

        if ($request->type === CashMovement::EXPENSE && $amount > $caja->expectedCash()) {
            DB::rollBack();
            return back()->withInput()->with('error', 'El gasto (S/ ' . number_format($amount, 2) . ') supera el efectivo disponible en caja (S/ ' . number_format($caja->expectedCash(), 2) . ').');
        }

        CashMovement::create([
            'company_id'       => $employee->company_id,
            'branch_id'        => $employee->branch_id,
            'cash_register_id' => $caja->id,
            'employee_id'      => $employee->id,
            'type'             => $request->type,
            'concept'          => $request->concept,
            'amount'           => $amount,
            'notes'            => $request->notes,
        ]);

        DB::commit();

        return redirect()->route('employee.cash-movements.index')
            ->with('success', ($request->type === CashMovement::EXPENSE ? 'Gasto' : 'Ingreso') . ' registrado correctamente.');
    }

    /**
     * Anula un movimiento mientras su caja siga abierta (solo quien la tiene abierta).
     * Exige el motivo y deja el registro en la bitácora.
     */
    public function void(VoidCashMovementRequest $request, CashMovement $movement)
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        abort_if(
            $movement->company_id !== $employee->company_id || !$caja || $movement->cash_register_id !== $caja->id,
            403,
            'Solo puedes anular movimientos de tu caja abierta.'
        );

        if (!$movement->status) {
            return back()->with('error', 'Ese movimiento ya estaba anulado.');
        }

        $movement->update([
            'status'      => 0,
            'void_reason' => $request->void_reason,
            'voided_at'   => now(),
            'voided_by'   => $employee->id,
        ]);

        AuditLog::record(
            'cash.movement_void',
            'Anuló ' . strtolower(CashMovement::TYPE_LABELS[$movement->type]) . ' «' . $movement->concept . '» de S/ ' . number_format((float) $movement->amount, 2),
            'Caja #' . $movement->cash_register_id,
            ['motivo' => $request->void_reason, 'monto' => (float) $movement->amount]
        );

        return redirect()->route('employee.cash-movements.index')->with('success', 'Movimiento anulado.');
    }
}
