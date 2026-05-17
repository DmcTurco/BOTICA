<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeIsBranchAdmin
{
    /**
     * Verifica que el empleado autenticado sea administrador de sede (role_id = 2).
     * Si no tiene el rol suficiente, retorna 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = auth()->guard('employee')->user();

        if (!$employee || $employee->role_id !== Role::BRANCH_ADMIN) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
