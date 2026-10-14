<?php

namespace App\Http\Requests\Admin;

use App\Models\SubscriptionPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPaymentRequest extends FormRequest
{
    /**
     * Solo el administrador del sistema registra pagos (la ruta ya exige auth:admin).
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Reglas para registrar un pago de la mensualidad.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'months'           => 'required|integer|min:1|max:24',
            'amount'           => 'required|numeric|min:0|max:999999',
            'paid_at'          => 'required|date|before_or_equal:today',
            'method'           => ['required', Rule::in(array_keys(SubscriptionPayment::METHODS))],
            'operation_number' => 'nullable|string|max:50',
            'notes'            => 'nullable|string|max:255',
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
            'months.required'        => 'Indica cuántos meses cubre el pago.',
            'months.min'             => 'El pago debe cubrir al menos 1 mes.',
            'months.max'             => 'Un pago puede cubrir como máximo 24 meses.',
            'amount.required'        => 'Indica el monto recibido.',
            'paid_at.required'       => 'Indica la fecha del pago.',
            'paid_at.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'method.required'        => 'Selecciona el medio de pago.',
        ];
    }
}
