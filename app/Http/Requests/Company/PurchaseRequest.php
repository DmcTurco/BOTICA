<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar una compra.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type'              => 'required|in:1,2,3',
            'document_number'            => 'nullable|max:30',
            'supplier'                   => 'nullable|max:150',
            'tax'                        => 'nullable|numeric|min:0',
            'notes'                      => 'nullable|max:500',
            'purchased_at'               => 'required|date',

            // Detalle: al menos un ítem
            'items'                      => 'required|array|min:1',
            'items.*.product_code'       => 'required|exists:products,code',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_cost'          => 'required|numeric|min:0',
            'items.*.expiration_date'    => 'nullable|date',
            'items.*.batch'              => 'nullable|max:30',
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
            'document_type.required'         => 'Debe seleccionar el tipo de documento.',
            'document_type.in'               => 'Tipo de documento no válido.',
            'purchased_at.required'          => 'La fecha de compra es obligatoria.',
            'items.required'                 => 'Debe agregar al menos un producto.',
            'items.min'                      => 'Debe agregar al menos un producto.',
            'items.*.product_code.required'  => 'Seleccione un producto.',
            'items.*.product_code.exists'    => 'El producto seleccionado no existe.',
            'items.*.quantity.required'      => 'La cantidad es obligatoria.',
            'items.*.quantity.min'           => 'La cantidad debe ser al menos 1.',
            'items.*.unit_cost.required'     => 'El costo unitario es obligatorio.',
            'items.*.unit_cost.min'          => 'El costo no puede ser negativo.',
        ];
    }
}
