<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\Request;
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
     * Muestra la página de apertura de caja.
     */
    public function showOpen()
    {
        $employee = auth()->guard('employee')->user();

        $cajaActiva = CashRegister::open()->where('employee_id', $employee->id)->first();

        if ($cajaActiva) {
            return redirect()->route('employee.orders.index')
                ->with('info', 'Ya tienes una caja abierta.');
        }

        return view('employee.pages.cash-register.open');
    }

    /**
     * Abre una nueva caja guardando el desglose de denominaciones.
     */
    public function open(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = auth()->guard('employee')->user();

        $cajaActiva = CashRegister::open()->where('employee_id', $employee->id)->first();

        if ($cajaActiva) {
            return redirect()->route('employee.orders.index')
                ->with('info', 'Ya tienes una caja abierta.');
        }

        [$denominaciones, $total] = $this->parsearDenominaciones($request);

        try {
            $caja = CashRegister::create([
                'company_id'            => $employee->company_id,
                'opening_amount'        => $total,
                'opening_denominations' => $denominaciones,
                'notes'                 => $request->notes,
                'status'                => 1,
                'opened_at'             => now(),
            ]);

            session(['cash_register_id' => $caja->id]);

            return redirect()->route('company.orders.index')
                ->with('success', 'Caja abierta con S/ ' . number_format($total, 2) . '. ¡Listo para vender!');

        } catch (\Exception $e) {
            Log::error('Error al abrir caja: ' . $e->getMessage());
            return redirect()->route('employee.cash-register.show-open')
                ->with('error', 'No se pudo abrir la caja. Intenta de nuevo.');
        }
    }

    /**
     * Muestra el formulario para editar la apertura de la caja activa.
     */
    public function edit()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::open()->where('employee_id', $employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.cash-register.show-open')
                ->with('error', 'No hay una caja abierta para editar.');
        }

        return view('employee.pages.cash-register.edit', compact('caja'));
    }

    /**
     * Actualiza el monto y denominaciones de la apertura de la caja activa.
     */
    public function update(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::open()->where('employee_id', $employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.cash-register.show-open')
                ->with('error', 'No hay una caja abierta para editar.');
        }

        [$denominaciones, $total] = $this->parsearDenominaciones($request);

        try {
            $caja->update([
                'opening_amount'        => $total,
                'opening_denominations' => $denominaciones,
                'notes'                 => $request->notes,
            ]);

            return redirect()->route('employee.cash-register.edit')
                ->with('success', 'Apertura actualizada. Nuevo total: S/ ' . number_format($total, 2));

        } catch (\Exception $e) {
            Log::error('Error al editar apertura de caja: ' . $e->getMessage());
            return redirect()->route('employee.cash-register.edit')
                ->with('error', 'No se pudo actualizar la apertura. Intenta de nuevo.');
        }
    }

    /**
     * Cierra la caja activa: calcula el total esperado y la diferencia.
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

        $caja = CashRegister::open()->where('employee_id', $employee->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('employee.home')
                ->with('error', 'No hay una caja abierta para cerrar.');
        }

        try {
            $expectedAmount = $caja->calcularTotalOrdenes();
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
            return redirect()->route('employee.home')
                ->with('error', 'No se pudo cerrar la caja. Intenta de nuevo.');
        }
    }

    /**
     * Devuelve el estado actual de la caja en JSON (para el badge del navbar).
     */
    public function status()
    {
        $employee = auth()->guard('employee')->user();

        $caja = CashRegister::open()
            ->where('employee_id', $employee->id)
            ->latest('opened_at')
            ->first();

        if (!$caja) {
            return response()->json(['open' => false]);
        }

        return response()->json([
            'open'           => true,
            'id'             => $caja->id,
            'opening_amount' => $caja->opening_amount,
            'opened_at'      => $caja->opened_at->format('d/m/Y H:i'),
            'total_orders'   => $caja->calcularTotalOrdenes(),
        ]);
    }
}
