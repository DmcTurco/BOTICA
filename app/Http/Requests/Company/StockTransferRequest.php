<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar un traspaso entre sedes.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = auth()->guard('employee')->user();

        return [
            // Destino: otra sede activa de la misma compañía
            'to_branch_id' => [
                'required',
                Rule::notIn([$employee->branch_id]),
                Rule::exists('branches', 'id')->where('company_id', $employee->company_id)->where('status', 1),
            ],
            'notes'                      => 'nullable|string|max:500',
            'items'                      => 'required|array|min:1',
            'items.*.product_code'       => [
                'required',
                'distinct',
                Rule::exists('products', 'code')->where('company_id', $employee->company_id)->whereNull('deleted_at'),
            ],
            'items.*.quantity'           => 'required|numeric|min:0.01|max:99999999',
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
            'to_branch_id.required'            => 'Selecciona la sede de destino.',
            'to_branch_id.not_in'           => 'La sede de destino debe ser distinta a la tuya.',
            'to_branch_id.exists'              => 'La sede de destino no es válida.',
            'items.required'                   => 'Agrega al menos un producto.',
            'items.min'                        => 'Agrega al menos un producto.',
            'items.*.product_code.required'    => 'Selecciona el producto.',
            'items.*.product_code.distinct'    => 'Un producto está repetido en el traspaso.',
            'items.*.product_code.exists'      => 'Un producto seleccionado no existe.',
            'items.*.quantity.required'        => 'Indica la cantidad de cada producto.',
            'items.*.quantity.min'             => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
