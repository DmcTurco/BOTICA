<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear o editar un proveedor
     * (se detecta la edición por el parámetro de ruta).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;
        $supplier  = $this->route('supplier');

        return [
            'ruc'     => ['nullable', 'digits:11', Rule::unique('suppliers', 'ruc')->where('company_id', $companyId)->ignore($supplier?->id)],
            'name'    => 'required|string|max:150',
            'contact' => 'nullable|string|max:100',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
            'address' => 'nullable|string|max:200',
            'notes'   => 'nullable|string|max:500',
            'status'  => 'required|in:0,1',
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
            'ruc.digits'    => 'El RUC debe tener 11 dígitos.',
            'ruc.unique'    => 'Ya tienes un proveedor registrado con ese RUC.',
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'email.email'   => 'Ingresa un correo válido.',
        ];
    }
}
