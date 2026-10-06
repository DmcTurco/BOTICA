<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashRegisterIsOpen
{
    /**
     * Verifica que el empleado tenga una caja normal abierta (aunque sea de un día anterior) antes de acceder al POS.
     * Las cajas históricas no habilitan el POS del día actual, salvo que se pida explícitamente
     * una propia con ?historical=ID (para registrar una venta olvidada de una fecha pasada).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = auth()->guard('employee')->user();

        // Venta sobre una caja histórica: debe ser del propio empleado, de su sede y seguir abierta/pendiente
        if ($request->filled('historical')) {
            $historica = CashRegister::where('id', (int) $request->input('historical'))
                ->where('employee_id', $employee->id)
                ->where('company_id', $employee->company_id)
                ->where('branch_id', $employee->branch_id)
                ->first();

            if ($historica && $historica->isHistorical() && $historica->isEditable()) {
                // La deja disponible para el controlador (no toca la caja normal de la sesión)
                $request->attributes->set('cash_register', $historica);

                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La caja histórica ya no está disponible para registrar ventas.',
                ], 403);
            }

            return redirect()->route('employee.home')
                ->with('error', 'La caja histórica ya no está disponible para registrar ventas.');
        }

        // Busca la caja abierta del propio empleado (no de otros ni históricas)
        $caja = CashRegister::currentOpen($employee->id)
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->latest('opened_at')
            ->first();

        if (!$caja) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => false,
                    'message'  => 'No tienes una caja abierta para hoy. Abre la caja antes de registrar una orden.',
                    'redirect' => route('employee.cash-register.show-open'),
                ], 403);
            }

            return redirect()->route('employee.cash-register.show-open')
                ->with('info', 'Abre tu caja para continuar.');
        }

        // Inyectar el ID de la caja en sesión para usarlo al crear órdenes
        session(['cash_register_id' => $caja->id]);

        return $next($request);
    }
}
