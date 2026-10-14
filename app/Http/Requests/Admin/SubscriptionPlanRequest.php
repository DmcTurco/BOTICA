<?php

namespace App\Http\Requests\Admin;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanRequest extends FormRequest
{
    /**
     * Solo el administrador del sistema configura el cobro (la ruta ya exige auth:admin).
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Reglas para configurar el plan de una empresa.
     *   Gratis:  no se cobra (sin más datos).
     *   Mensual: monto, ciclo y fecha. Si la empresa estaba gratis, la fecha es el fin de la
     *            prueba (desde hoy); si ya pagaba, es una corrección de "cubierto hasta".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Company $company */
        $company    = $this->route('company');
        $activating = $company->paid_until === null; // pasa de gratis a mensual

        return [
            'plan'          => 'required|in:free,monthly',
            'monthly_fee'   => 'required_if:plan,monthly|nullable|numeric|min:0.01|max:99999',
            'billing_cycle' => ['required_if:plan,monthly', 'nullable', Rule::in(array_keys(Company::CYCLE_LABELS))],
            'covered_until' => array_filter([
                'required_if:plan,monthly', 'nullable', 'date',
                $activating ? 'after_or_equal:today' : null,
            ]),
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan.required'                => 'Elige el plan: gratis o mensual.',
            'monthly_fee.required_if'      => 'Indica cuánto paga al mes.',
            'monthly_fee.min'              => 'La mensualidad debe ser mayor a 0 (si no paga, elige el plan gratis).',
            'billing_cycle.required_if'    => 'Elige cuándo vence cada mes.',
            'covered_until.required_if'    => 'Indica la fecha.',
            'covered_until.after_or_equal' => 'La prueba no puede terminar antes de hoy.',
        ];
    }
}
