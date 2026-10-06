<?php

namespace App\Http\Responses;

use App\Models\CashRegister;
use App\Models\Employee;
use App\MyApp;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Redirección tras iniciar sesión.
     * Un empleado que puede abrir caja y no tiene una caja abierta va directo a la
     * pantalla de apertura: las ventas quedan bloqueadas hasta que la abra.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        if (config('fortify.guard') === MyApp::EMPLOYEE_SUBDIR) {
            $employee = auth()->guard('employee')->user();

            if ($employee
                && $employee->hasPrivilege(Employee::PRIV_ABRIR_CAJA)
                && !CashRegister::currentOpen($employee->id)->exists()
            ) {
                return redirect()->route('employee.cash-register.show-open')
                    ->with('info', 'Abre tu caja para empezar a vender.');
            }
        }

        return redirect()->intended(config('fortify.home'));
    }
}
