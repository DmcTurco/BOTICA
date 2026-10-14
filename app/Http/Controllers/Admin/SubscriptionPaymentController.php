<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPaymentRequest;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

/**
 * Pagos de la mensualidad (se ven y registran en Suscripciones → empresa).
 */
class SubscriptionPaymentController extends Controller
{
    /**
     * Registra un pago y extiende "cubierto hasta" los meses pagados, según el ciclo de cobro.
     * Si la empresa estaba bloqueada, vuelve a tener acceso de inmediato.
     */
    public function store(SubscriptionPaymentRequest $request, Company $company)
    {
        // A una empresa gratis no se le registran pagos: primero se le activa el cobro mensual
        if ($company->paid_until === null) {
            return back()->withInput()->with('error', 'Esta empresa tiene plan gratis. Primero activa el cobro mensual en "Plan".');
        }

        $payment = $company->registerPayment($request->validated(), auth()->guard('admin')->id());

        return redirect()->route('admin.subscriptions.show', $company)
            ->with('success', "Pago registrado. {$company->name} queda cubierta hasta el {$payment->period_to->format('d/m/Y')}.");
    }

    /**
     * Anula el último pago registrado (para corregir un error): "cubierto hasta"
     * vuelve a la fecha anterior a ese pago. Solo se puede anular el más reciente.
     */
    public function destroy(Company $company, SubscriptionPayment $payment)
    {
        abort_if($payment->company_id !== $company->id, 404);

        $latest = $company->subscriptionPayments()->orderByDesc('period_to')->orderByDesc('id')->first();

        if ($latest?->id !== $payment->id) {
            return back()->with('error', 'Solo se puede anular el último pago registrado.');
        }

        DB::transaction(function () use ($company, $payment) {
            $company->update(['paid_until' => $payment->period_from->copy()->subDay()]);
            $payment->delete();
        });

        return redirect()->route('admin.subscriptions.show', $company)
            ->with('success', "Pago anulado. {$company->name} queda cubierta hasta el {$company->paid_until->format('d/m/Y')}.");
    }
}
