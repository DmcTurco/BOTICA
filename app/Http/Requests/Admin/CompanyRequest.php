<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    /**
     * Solo el administrador del sistema gestiona compañías (la ruta ya exige auth:admin).
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Reglas para crear o editar una compañía (botica cliente).
     * Store: exige contraseña y el nombre de la primera sede. Update: la contraseña es opcional.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company  = $this->route('company'); // null en store, modelo en update
        $isUpdate = $company !== null;

        $rules = [
            'name'     => 'required|string|max:150',
            'ruc'      => ['nullable', 'digits:11', Rule::unique('companies', 'ruc')->ignore($company?->id)],
            'address'  => 'nullable|string|max:200',
            'phone'    => 'nullable|string|max:30',
            'email'    => ['required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($company?->id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
        // El cobro (plan, prueba, mensualidad) se configura aparte en Suscripciones

        // La primera sede solo se pide al crear (después la gestiona la propia empresa)
        if (!$isUpdate) {
            $rules['branch_name']    = 'required|string|max:100';
            $rules['branch_address'] = 'nullable|string|max:200';
            $rules['load_products']  = 'nullable|boolean';   // precargar el catálogo de productos (stock 0)
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'El nombre de la botica es obligatorio.',
            'ruc.digits'           => 'El RUC debe tener 11 dígitos.',
            'ruc.unique'           => 'Ese RUC ya está registrado en otra empresa.',
            'email.required'       => 'El correo es obligatorio (es el usuario para entrar al panel).',
            'email.email'          => 'Ingresa un correo válido.',
            'email.unique'         => 'Ese correo ya lo usa otra empresa.',
            'password.required'    => 'La contraseña es obligatoria.',
            'password.min'         => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'   => 'La confirmación de la contraseña no coincide.',
            'branch_name.required' => 'El nombre de la primera sede es obligatorio.',
        ];
    }
}
