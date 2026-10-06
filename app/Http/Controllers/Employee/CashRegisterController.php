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

        // Si ya tiene una caja normal abierta no se bloquea la pantalla: aún puede abrir
        // una caja de fecha pasada (por ejemplo, para registrar una venta que olvidó).
        $cajaAbierta = CashRegister::currentOpen($employee->id)->first();

        $today = Carbon::today()->toDateString();

        // Con una caja abierta solo tiene sentido elegir fechas anteriores a hoy
        $maxDate = $cajaAbierta ? Carbon::yesterday()->toDateString() : $today;

        // Denominaciones de aperturas anteriores del empleado, por fecha, para precargar
        // los billetes y monedas al abrir de nuevo una caja de una fecha pasada.
        // Si hubo varias aperturas con datos en la misma fecha, prevalece la más reciente.
        $previousDenominations = CashRegister::where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('register_date', '<', $today)
            ->orderBy('id')
            ->get(['register_date', 'opening_denominations'])
            ->filter(fn ($c) => !empty($c->opening_denominations)) // ignora aperturas sin billetes/monedas
            ->mapWithKeys(fn ($c) => [
                $c->register_date->toDateString() => collect($c->opening_denominations ?? [])
                    ->mapWithKeys(fn ($d) => [
                        'den_' . str_replace('.', '_', $d['valor']) => (int) $d['cantidad'],
                    ]),
            ]);

        return view('employee.pages.cash-register.open', compact('today', 'maxDate', 'cajaAbierta', 'previousDenominations'));
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

        // Verificar que no exista ya una caja abierta del mismo empleado.
        // Caja de hoy: no se permite si ya hay una normal abierta (aunque sea de otro día)
        $cajaExistente = $isHistorical
            ? CashRegister::open()
                ->where('employee_id', $employee->id)
                ->whereDate('register_date', $registerDate)
                ->first()
            : CashRegister::currentOpen($employee->id)->first();

        if ($cajaExistente) {
            // Ya hay una caja histórica abierta para esa fecha: se continúa en ella
            if ($isHistorical && $cajaExistente->isHistorical() && $cajaExistente->isEditable()) {
                return redirect()->route('employee.cash-register.historical', $cajaExistente)
                    ->with('success', "Ya tenías una caja abierta para el {$registerDate->format('d/m/Y')}. Puedes continuar aquí.");
            }

            $label = $isHistorical ? " para el {$registerDate->format('d/m/Y')}" : '';

            // withInput conserva la fecha y las cantidades elegidas
            return back()->withInput()->with('error', "Ya tienes una caja abierta{$label}.");
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

        // Una caja sin ventas no tiene nada que validar
        if ($cashRegister->orders()->where('status', 1)->doesntExist()) {
            return back()->with('error', 'No hay ventas registradas en esta caja. Registra al menos una venta o descarta la caja.');
        }

        // Efectivo esperado en el cajón: apertura + ventas en efectivo
        $expectedAmount = $cashRegister->expectedCash();

        $cashRegister->update([
            'expected_amount' => $expectedAmount,
            'status'          => 0,
            'closed_at'       => now(),
        ]);

        return redirect()->route('employee.home')
            ->with('success', "Caja del {$cashRegister->register_date->format('d/m/Y')} cerrada y enviada a validación.");
    }

    /**
     * Descarta una caja histórica abierta que no tiene ninguna venta
     * (por ejemplo, abierta por error). Si tiene ventas no se puede descartar.
     */
    public function discardHistorical(CashRegister $cashRegister)
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->employee_id !== $employee->id ||
            $cashRegister->company_id  !== $employee->company_id,
            403
        );
        abort_if(!$cashRegister->isHistorical() || !$cashRegister->isEditable(), 403);

        if ($cashRegister->orders()->exists()) {
            return back()->with('error', 'Esta caja tiene ventas registradas y no se puede descartar.');
        }

        $date = $cashRegister->register_date->format('d/m/Y');
        $cashRegister->delete();

        return redirect()->route('employee.home')
            ->with('success', "Caja histórica del {$date} descartada.");
    }

    /**
     * Formulario para editar la apertura de la caja de hoy.
     */
    public function edit()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

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

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

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
     * Pantalla de cierre de caja: resumen de ventas por forma de pago,
     * efectivo esperado y formulario con el efectivo contado.
     */
    public function showClose()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.home')
                ->with('error', 'No hay una caja abierta para cerrar.');
        }

        return view('employee.pages.cash-register.close', [
            'caja'          => $caja,
            'paymentTotals' => $caja->totalsByPaymentType(),
            'paymentLabels' => CashRegister::PAYMENT_TYPE_LABELS,
            'totalOrders'   => $caja->totalOrders(),
            'cashTotal'     => $caja->totalCash(),
            'expectedCash'  => $caja->expectedCash(),
        ]);
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

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.home')
                ->with('error', 'No hay una caja abierta para cerrar.');
        }

        try {
            // Efectivo esperado en el cajón: apertura + ventas en efectivo
            $expectedAmount = $caja->expectedCash();
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
                ->with('success', 'Caja cerrada. Efectivo esperado: S/ ' . number_format($expectedAmount, 2) .
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

        $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return response()->json(['open' => false]);
        }

        return response()->json([
            'open'           => true,
            'id'             => $caja->id,
            'opening_amount' => $caja->opening_amount,
            'opened_at'      => $caja->opened_at->format('d/m/Y H:i'),
            'total_orders'   => $caja->totalOrders(),
            'cash_total'     => $caja->totalCash(),
            'expected_cash'  => $caja->expectedCash(),
            'by_payment'     => collect($caja->totalsByPaymentType())
                ->map(fn ($total, $type) => [
                    'label' => CashRegister::PAYMENT_TYPE_LABELS[$type],
                    'total' => $total,
                ])
                ->values(),
        ]);
    }
}
