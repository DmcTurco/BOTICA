<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\MyApp;
use Illuminate\Http\Request;

class SuspendedController extends Controller
{
    /**
     * Pantalla de servicio suspendido por falta de pago (paneles de empresa y de empleado).
     * Si la mensualidad ya está al día, vuelve al inicio del panel.
     */
    public function __invoke(Request $request)
    {
        $guard   = $request->routeIs('company.*') ? MyApp::COMPANY_SUBDIR : MyApp::EMPLOYEE_SUBDIR;
        $user    = auth()->guard($guard)->user();
        $company = $user instanceof Company ? $user : $user->company;

        if ($company->hasActiveSubscription()) {
            return redirect()->route("{$guard}.home");
        }

        return view('subscription.suspended', [
            'company'     => $company,
            'isCompany'   => $guard === MyApp::COMPANY_SUBDIR,
            'paymentInfo' => config('billing.payment_info'),
        ]);
    }
}
