<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    /**
     * Bloquea el panel de una empresa (y el de sus empleados) cuando su mensualidad
     * está suspendida: vencida y pasados los días de gracia. Se aplica después de auth.
     *
     * @param string $guard company | employee
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $user    = auth()->guard($guard)->user();
        $company = $user instanceof Company ? $user : $user?->company;

        if (!$company || $company->hasActiveSubscription()) {
            return $next($request);
        }

        $message = 'El servicio está suspendido por falta de pago de la mensualidad.';

        // Peticiones AJAX (por ejemplo, confirmar una venta): respuesta JSON que el panel muestra
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 402);
        }

        return redirect()->route("{$guard}.suspended");
    }
}
