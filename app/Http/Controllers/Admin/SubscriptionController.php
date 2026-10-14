<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

/**
 * Suscripciones: todo lo del cobro de la mensualidad de cada empresa
 * (plan, prueba gratis, ciclo de cobro, pagos). Los datos y el acceso están en Empresas.
 */
class SubscriptionController extends Controller
{
    /** Orden de la lista: primero las que hay que cobrar */
    const STATUS_ORDER = [
        Company::SUB_SUSPENDED => 0,
        Company::SUB_OVERDUE   => 1,
        Company::SUB_DUE_SOON  => 2,
        Company::SUB_TRIAL     => 3,
        Company::SUB_ACTIVE    => 4,
        Company::SUB_EXEMPT    => 5,
    ];

    /**
     * Lista de empresas con su plan, estado y vencimiento. Filtro opcional por estado.
     */
    public function index(Request $request)
    {
        $companies = Company::orderBy('name')->get()
            ->sortBy(fn (Company $c) => [self::STATUS_ORDER[$c->subscriptionStatus()], $c->paid_until?->timestamp ?? PHP_INT_MAX])
            ->values();

        $counts = $companies->countBy(fn (Company $c) => $c->subscriptionStatus());

        if ($request->filled('estado')) {
            $companies = $companies->filter(fn (Company $c) => $c->subscriptionStatus() === $request->estado)->values();
        }

        return view('admin.pages.subscriptions.index', compact('companies', 'counts'));
    }

    /**
     * Suscripción de una empresa: estado, configuración del plan, registrar pago e historial.
     */
    public function show(Company $company)
    {
        $payments = $company->subscriptionPayments()->with('admin:id,name')
            ->orderByDesc('period_to')->orderByDesc('id')
            ->get();

        // Hasta qué fecha quedaría cubierta según los meses que se paguen (según el ciclo de cobro)
        $base     = $company->nextPaymentBase();
        $coverage = collect(range(1, 24))->mapWithKeys(fn ($m) => [$m => $company->periodEndFor($base, $m)->format('d/m/Y')]);

        // Fin de prueba sugerido al activar el cobro: un mes desde hoy (por fecha) o fin de mes
        $trialSuggestion = [
            Company::CYCLE_DATE      => today()->addMonthNoOverflow()->format('Y-m-d'),
            Company::CYCLE_MONTH_END => today()->endOfMonth()->format('Y-m-d'),
        ];

        return view('admin.pages.subscriptions.show', [
            'company'         => $company,
            'payments'        => $payments,
            'methods'         => SubscriptionPayment::METHODS,
            'coverFrom'       => $base->copy()->addDay(),
            'coverage'        => $coverage,
            'trialSuggestion' => $trialSuggestion,
        ]);
    }

    /**
     * Guarda el plan de la empresa.
     *   Gratis:  se quita el cobro (nunca vence ni se bloquea).
     *   Mensual desde gratis: empieza la prueba gratis desde hoy hasta la fecha indicada.
     *   Mensual ya activo: cambia monto/ciclo y permite corregir "cubierto hasta".
     */
    public function update(SubscriptionPlanRequest $request, Company $company)
    {
        if ($request->plan === 'free') {
            $company->update(['monthly_fee' => 0, 'billing_cycle' => Company::CYCLE_DATE, 'trial_until' => null, 'paid_until' => null]);

            return redirect()->route('admin.subscriptions.show', $company)
                ->with('success', "{$company->name} queda con plan gratis: no se le cobra.");
        }

        $data = [
            'monthly_fee'   => $request->monthly_fee,
            'billing_cycle' => $request->billing_cycle,
            'paid_until'    => $request->covered_until,
        ];

        $activating = $company->paid_until === null;

        if ($activating) {
            $data['trial_until'] = $request->covered_until;
        }

        $company->update($data);

        $message = $activating
            ? "Cobro activado. {$company->name} usa el sistema gratis hasta el {$company->paid_until->format('d/m/Y')}; después debe pagar S/ " . number_format($company->monthly_fee, 2) . ' al mes.'
            : "Suscripción de {$company->name} actualizada.";

        return redirect()->route('admin.subscriptions.show', $company)->with('success', $message);
    }
}
