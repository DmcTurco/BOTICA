<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class LocalDataRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para editar los datos de contacto de la sede.
     * El código de establecimiento SUNAT y el estado los administra la empresa, no la sede.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:100',
            'address' => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
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
            'name.required' => 'El nombre de la sede es obligatorio.',
            'email.email'   => 'Ingresa un correo válido.',
        ];
    }
}
