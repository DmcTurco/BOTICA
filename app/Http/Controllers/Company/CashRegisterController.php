<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    /**
     * Abre una nueva caja con el monto inicial declarado.
     */
    public function open(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ], [
            'opening_amount.required' => 'Ingresa el monto inicial de caja.',
            'opening_amount.numeric'  => 'El monto debe ser un número válido.',
            'opening_amount.min'      => 'El monto no puede ser negativo.',
        ]);

        $company = auth()->guard('company')->user();

        // Verificar que no haya una caja ya abierta
        $cajaActiva = CashRegister::open()->where('company_id', $company->id)->first();

        if ($cajaActiva) {
            return redirect()->route('company.orders.index')
                ->with('info', 'Ya tienes una caja abierta.');
        }

        try {
            $caja = CashRegister::create([
                'company_id'     => $company->id,
                'opening_amount' => $request->opening_amount,
                'status'         => 1,
                'opened_at'      => now(),
            ]);

            // Guardar ID en sesión para vincular órdenes
            session(['cash_register_id' => $caja->id]);

            return redirect()->route('company.orders.index')
                ->with('success', 'Caja abierta correctamente. ¡Listo para vender!');

        } catch (\Exception $e) {
            Log::error('Error al abrir caja: ' . $e->getMessage());
            return redirect()->route('company.home')
                ->with('error', 'No se pudo abrir la caja. Intenta de nuevo.');
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

        $company = auth()->guard('company')->user();

        $caja = CashRegister::open()->where('company_id', $company->id)->latest('opened_at')->first();

        if (!$caja) {
            return redirect()->route('company.home')
                ->with('error', 'No hay una caja abierta para cerrar.');
        }

        try {
            // Suma todas las órdenes activas registradas en esta caja
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

            // Limpiar sesión de caja
            session()->forget('cash_register_id');

            return redirect()->route('company.home')
                ->with('success', 'Caja cerrada. Total esperado: S/ ' . number_format($expectedAmount, 2) .
                    ' | Diferencia: S/ ' . number_format($difference, 2));

        } catch (\Exception $e) {
            Log::error('Error al cerrar caja: ' . $e->getMessage());
            return redirect()->route('company.home')
                ->with('error', 'No se pudo cerrar la caja. Intenta de nuevo.');
        }
    }

    /**
     * Devuelve el estado actual de la caja en JSON (para el badge del navbar).
     */
    public function status()
    {
        $company = auth()->guard('company')->user();

        $caja = CashRegister::open()
            ->where('company_id', $company->id)
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
