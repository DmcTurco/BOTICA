<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAdjustmentRequest extends FormRequest
{
    /** Motivos de ajuste que ofrece la pantalla */
    const REASONS = [
        'conteo'   => 'Error de conteo / inventario físico',
        'merma'    => 'Merma o rotura',
        'vencido'  => 'Producto vencido',
        'perdida'  => 'Pérdida o robo',
        'donacion' => 'Donación o muestra',
        'otro'     => 'Otro (explicar en la nota)',
    ];

    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para ajustar el stock de un producto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;

        return [
            'product_code' => [
                'required',
                Rule::exists('products', 'code')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            // set = fijar el stock contado · add = sumar · subtract = restar
            'mode'     => 'required|in:set,add,subtract',
            'quantity' => 'required|numeric|min:0|max:99999999',
            'reason'   => ['required', Rule::in(array_keys(self::REASONS))],
            'notes'    => 'nullable|string|max:255|required_if:reason,otro',
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
            'product_code.required' => 'Selecciona el producto a ajustar.',
            'product_code.exists'   => 'El producto seleccionado no existe.',
            'mode.required'         => 'Indica el tipo de ajuste.',
            'quantity.required'     => 'Indica la cantidad.',
            'quantity.min'          => 'La cantidad no puede ser negativa.',
            'reason.required'       => 'Selecciona el motivo del ajuste.',
            'notes.required_if'     => 'Explica el motivo del ajuste en la nota.',
        ];
    }
}
