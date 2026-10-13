<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyProfileRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de los datos generales de la empresa.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('company')->id();

        return [
            'name'    => 'required|string|max:150',
            'ruc'     => ['nullable', 'digits:11', Rule::unique('companies', 'ruc')->ignore($companyId)],
            'address' => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:30',
            'email'   => ['required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($companyId)],
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
            'name.required'  => 'El nombre de la botica es obligatorio.',
            'ruc.digits'     => 'El RUC debe tener 11 dígitos.',
            'ruc.unique'     => 'Ese RUC ya está registrado en otra empresa.',
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'Ingresa un correo válido.',
            'email.unique'   => 'Ese correo ya lo usa otra empresa.',
        ];
    }
}
