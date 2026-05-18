<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashRegisterIsOpen
{
    /**
     * Verifica que el empleado tenga una caja abierta para HOY antes de acceder al POS.
     * Las cajas históricas no habilitan el POS del día actual.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = auth()->guard('employee')->user();

        // Busca la caja del día del propio empleado (no de otros ni históricas)
        $caja = CashRegister::todayOpen($employee->id)
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

            return redirect()->route('employee.cash-register.show-open');
        }

        // Inyectar el ID de la caja en sesión para usarlo al crear órdenes
        session(['cash_register_id' => $caja->id]);

        return $next($request);
    }
}
