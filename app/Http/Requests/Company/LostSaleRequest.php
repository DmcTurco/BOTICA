<?php

namespace App\Http\Requests\Company;

use App\Models\LostSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LostSaleRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar una venta perdida.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;

        return [
            'product_name' => 'required|string|max:150',
            'product_code' => [
                'nullable',
                Rule::exists('products', 'code')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'quantity' => 'required|numeric|min:0.01|max:99999',
            'reason'   => ['required', Rule::in(array_keys(LostSale::REASONS))],
            'notes'    => 'nullable|string|max:255',
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
            'product_name.required' => 'Indica qué producto pidió el cliente.',
            'quantity.required'     => 'Indica la cantidad pedida.',
            'quantity.min'          => 'La cantidad debe ser mayor a 0.',
            'reason.required'       => 'Selecciona el motivo.',
        ];
    }
}
