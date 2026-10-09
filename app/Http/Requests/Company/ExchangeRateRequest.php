<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeRateRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar el tipo de cambio de un día.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rate_date' => 'required|date|before_or_equal:today',
            'buy_rate'  => 'required|numeric|min:0.0001|max:9999',
            'sell_rate' => 'required|numeric|min:0.0001|max:9999|gte:buy_rate',
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
            'rate_date.required'        => 'Indica la fecha del tipo de cambio.',
            'rate_date.before_or_equal' => 'La fecha no puede ser futura.',
            'buy_rate.required'         => 'Indica el tipo de cambio de compra.',
            'sell_rate.required'        => 'Indica el tipo de cambio de venta.',
            'sell_rate.gte'             => 'El tipo de cambio de venta no puede ser menor al de compra.',
        ];
    }
}
