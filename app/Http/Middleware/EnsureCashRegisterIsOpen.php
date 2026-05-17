<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashRegisterIsOpen
{
    /**
     * Verifica que exista una caja abierta antes de acceder al punto de venta.
     * Si no hay caja, redirige al dashboard con una señal para abrir el modal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = auth()->guard('employee')->user();

        // Busca una caja abierta en la sede del empleado
        $caja = CashRegister::open()
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->latest('opened_at')
            ->first();

        if (!$caja) {
            // Si es una petición AJAX (ej. store de orden), devuelve JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta. Abre la caja antes de registrar una orden.',
                    'redirect' => route('employee.cash-register.show-open'),
                ], 403);
            }

            // Redirige a la página dedicada de apertura de caja
            return redirect()->route('employee.cash-register.show-open');
        }

        // Inyecta el ID de la caja en la sesión para usarlo al crear órdenes
        session(['cash_register_id' => $caja->id]);

        return $next($request);
    }
}
