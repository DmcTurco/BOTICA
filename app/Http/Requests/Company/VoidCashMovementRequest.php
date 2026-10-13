<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class VoidCashMovementRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     * La pertenencia del movimiento a la caja abierta se valida en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para anular un gasto u otro ingreso de caja.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'void_reason' => 'required|string|min:3|max:255',
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
            'void_reason.required' => 'Indica el motivo de la anulación.',
            'void_reason.min'      => 'El motivo de la anulación es muy corto.',
            'void_reason.max'      => 'El motivo no puede superar los 255 caracteres.',
        ];
    }
}
