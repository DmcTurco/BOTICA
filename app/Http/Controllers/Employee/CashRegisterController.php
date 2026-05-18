<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    /**
     * Denominaciones válidas del sol peruano.
     */
    private const DENOMINACIONES = [
        'billetes' => [200, 100, 50, 20, 10],
        'monedas'  => [5, 2, 1, 0.50, 0.20, 0.10],
    ];

    /**
     * Construye el array de denominaciones a partir del request.
     * Devuelve [denominaciones[], total_calculado].
     */
    private function parsearDenominaciones(Request $request): array
    {
        $denominaciones = [];
        $total          = 0;

        foreach (self::DENOMINACIONES as $grupo => $valores) {
            foreach ($valores as $valor) {
                $key      = 'den_' . str_replace('.', '_', $valor);
                $cantidad = max(0, (int) $request->input($key, 0));
                $subtotal = round($cantidad * $valor, 2);

                if ($cantidad > 0) {
                    $denominaciones[] = [
                        'valor'    => $valor,
                        'grupo'    => $grupo,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal,
                    ];
                }

                $total += $subtotal;
            }
        }

        return [$denominaciones, round($total, 2)];
    }

    /**
     * Muestra la pantalla de apertura de caja.
     * El empleado puede elegir hoy o una fecha pasada (nunca futura).
     */
    public function showOpen()
    {
        $employee = auth()->guard('employee')->user();

        // Si ya tiene caja de hoy abierta, redirige al POS
        $cajaHoy = CashRegister::todayOpen($employee->id)->first();
        if ($cajaHoy) {
            return redirect()->route('employee.orders.index')
                ->with('info', 'Ya tienes una caja abierta para hoy.');
        }

        $today = Carbon::today()->toDateString();

        return view('employee.pages.cash-register.open', compact('today'));
    }

    /**
     * Abre una caja para la fecha indicada.
     * Fecha = hoy → caja normal (APPROVAL_NORMAL).
     * Fecha < hoy → caja histórica (APPROVAL_PENDING), requiere validación.
     */
    public function open(Request $request)
    {
        $request->validate([
            'register_date' => ['required', 'date', 'before_or_equal:today'],
            'notes'         => 'nullable|string|max:500',
        ], [
            'register_date.required'        => 'Selecciona la fecha de la caja.',
            'register_date.before_or_equal' => 'No puedes abrir una caja para una fecha futura.',
        ]);

        $employee     = auth()->guard('employee')->user();
        $registerDate = Carbon::parse($request->register_date);
        $isHistorical = $registerDate->lt(Carbon::today());

        // Verificar que no exista ya una caja abierta del mismo empleado para esa fecha
        $cajaExistente = CashRegister::open()
            ->where('employee_id', $employee->id)
            ->whereDate('register_date', $registerDate)
            ->first();

        if ($cajaExistente) {
            $label = $isHistorical ? "el {$registerDate->format('d/m/Y')}" : 'hoy';
            return back()->with('error', "Ya tienes una caja abierta para {$label}.");
        }

        [$denominaciones, $total] = $this->parsearDenominaciones($request);

        try {
            $caja = CashRegister::create([
                'company_id'            => $employee->company_id,
                'branch_id'             => $employee->branch_id,
                'employee_id'           => $employee->id,
                'register_date'         => $registerDate->toDateString(),
                'opening_amount'        => $total,
                'opening_denominations' => $denominaciones,
                'notes'                 => $request->notes,
                'status'                => 1,
                'approval_status'       => $isHistorical
                    ? CashRegister::APPROVAL_PENDING
                    : CashRegister::APPROVAL_NORMAL,
                'opened_at'             => now(),
            ]);

            if (!$isHistorical) {
                session(['cash_register_id' => $caja->id]);
                return redirect()->route('employee.orders.index')
                    ->with('success', 'Caja abierta con S/ ' . number_format($total, 2) . '. ¡Listo para vender!');
            }

            return redirect()->route('employee.cash-register.historical', $caja)
                ->with('success', "Caja histórica abierta para el {$registerDate->format('d/m/Y')}. Las ventas quedarán pendientes de validación.");

        } catch (\Exception $e) {
            Log::error('Error al abrir caja: ' . $e->getMessage());
            return back()->with('error', 'No se pudo abrir la caja. Intenta de nuevo.');
        }
    }

    /**
     * Muestra la vista de una caja histórica con sus órdenes.
     */
    public function historical(CashRegister $cashRegister)
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->employee_id !== $employee->id ||
            $cashRegister->company_id  !== $employee->company_id,
            403
        );
        abort_if(!$cashRegister->isHistorical(), 404);

        $cashRegister->load(['orders.items', 'employee']);

        return view('employee.pages.cash-register.historical', compact('cashRegister'));
    }

    /**
     * Cierra una caja histórica y la envía a validación del branch_admin.
     */
    public function closeHistorical(Request $request, CashRegister $cashRegister)
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->employee_id !== $employee->id ||
            $cashRegister->company_id  !== $employee->company_id,
            403
        );
        abort_if(!$cashRegister->isHistorical() || $cashRegister->status !== 1, 403);

        $expectedAmount = $cashRegister->totalOrders();

        $cashRegister->update([
            'expected_amount' => $expectedAmount,
            'status'          => 0,
            'closed_at'       => now(),
        ]);

        return redirect()->route('employee.home')
            ->with('success', "Caja del {$cashRegister->register_date->format('d/m/Y')} cerrada y enviada a validación.");
    }

    /**
     * Formulario para editar la apertura de la caja de hoy.
     */
    public function edit()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::todayOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.cash-register.show-open')
                ->with('error', 'No hay una caja abierta para hoy.');
        }

        return view('employee.pages.cash-register.edit', compact('caja'));
    }

    /**
     * Actualiza el monto de apertura de la caja de hoy.
     */
    public function update(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::todayOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return back()->with('error', 'No hay una caja abierta para editar.');
        }

        [$denominaciones, $total] = $this->parsearDenominaciones($request);

        try {
            $caja->update([
                'opening_amount'        => $total,
                'opening_denominations' => $denominaciones,
                'notes'                 => $request->notes,
            ]);

            return back()->with('success', 'Apertura actualizada. Nuevo total: S/ ' . number_format($total, 2));

        } catch (\Exception $e) {
            Log::error('Error al editar apertura: ' . $e->getMessage());
            return back()->with('error', 'No se pudo actualizar la apertura.');
        }
    }

    /**
     * Cierra la caja del día del empleado.
     */
    public function close(Request $request)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ], [
            'closing_amount.required' => 'Ingresa el monto contado al cierre.',
            'closing_amount.numeric'  => 'El monto debe ser un número válido.',
        ]);

        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::todayOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.home')
                ->with('error', 'No hay una caja abierta para cerrar.');
        }

        try {
            $expectedAmount = $caja->totalOrders();
            $difference     = (float) $request->closing_amount - $expectedAmount;

            $caja->update([
                'closing_amount'  => $request->closing_amount,
                'expected_amount' => $expectedAmount,
                'difference'      => $difference,
                'notes'           => $request->notes,
                'status'          => 0,
                'closed_at'       => now(),
            ]);

            session()->forget('cash_register_id');

            return redirect()->route('employee.home')
                ->with('success', 'Caja cerrada. Total esperado: S/ ' . number_format($expectedAmount, 2) .
                    ' | Diferencia: S/ ' . number_format($difference, 2));

        } catch (\Exception $e) {
            Log::error('Error al cerrar caja: ' . $e->getMessage());
            return back()->with('error', 'No se pudo cerrar la caja.');
        }
    }

    /**
     * Estado actual de la caja de hoy para el badge del navbar (JSON).
     */
    public function status()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::todayOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return response()->json(['open' => false]);
        }

        return response()->json([
            'open'           => true,
            'id'             => $caja->id,
            'opening_amount' => $caja->opening_amount,
            'opened_at'      => $caja->opened_at->format('d/m/Y H:i'),
            'total_orders'   => $caja->totalOrders(),
        ]);
    }
}
