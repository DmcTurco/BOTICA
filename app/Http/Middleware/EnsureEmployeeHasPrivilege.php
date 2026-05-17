<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeHasPrivilege
{
    /**
     * Verifica que el empleado tenga el privilegio requerido.
     *
     * Uso en rutas:
     *   ->middleware('privilege:ver_ventas')   — privilegio específico
     *   ->middleware('privilege:any')           — al menos un privilegio (acceso base)
     *
     * Los branch_admin siempre pasan. Los empleados (role_id=3) sin ningún
     * privilegio asignado solo pueden ver el dashboard.
     */
    public function handle(Request $request, Closure $next, string $privilege): Response
    {
        $employee = auth()->guard('employee')->user();

        $allowed = $privilege === 'any'
            ? $employee?->hasAnyPrivilege()
            : $employee?->hasPrivilege($privilege);

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            return redirect()->route('employee.home')
                ->with('error', 'No tienes permiso para acceder a esa sección.');
        }

        return $next($request);
    }
}
