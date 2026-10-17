<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Conteo de inventario inicial desde la pantalla: una fila por producto, todas opcionales
 * (solo se procesa lo que se llenó).
 */
class InitialInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('employee')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows'              => 'required|array',
            'rows.*.quantity'   => 'nullable|numeric|min:0|max:9999999',
            'rows.*.cost'       => 'nullable|numeric|min:0|max:99999999',
            'rows.*.price'      => 'nullable|numeric|min:0|max:99999999',
            'rows.*.batch'      => 'nullable|string|max:30',
            'rows.*.expiration' => 'nullable|date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rows.required'            => 'No hay productos para guardar.',
            'rows.*.quantity.numeric'  => 'La cantidad contada debe ser un número.',
            'rows.*.quantity.min'      => 'La cantidad contada no puede ser negativa.',
            'rows.*.cost.numeric'      => 'El costo debe ser un número.',
            'rows.*.cost.min'          => 'El costo no puede ser negativo.',
            'rows.*.price.numeric'     => 'El precio de venta debe ser un número.',
            'rows.*.price.min'         => 'El precio de venta no puede ser negativo.',
            'rows.*.batch.max'         => 'El lote tiene como máximo 30 caracteres.',
            'rows.*.expiration.date'   => 'El vencimiento no es una fecha válida.',
        ];
    }
}
