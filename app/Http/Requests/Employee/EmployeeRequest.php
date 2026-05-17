<?php

namespace App\Http\Requests\Employee;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Solo los branch_admin pueden gestionar empleados.
     */
    public function authorize(): bool
    {
        return auth()->guard('employee')->check();
    }

    /**
     * Reglas de validación — detecta store vs update por la ruta.
     */
    public function rules(): array
    {
        // En update, el empleado a editar viene como parámetro de ruta
        $employeeId = $this->route('employee')?->id;
        $isUpdate   = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId),
            ],
            'password'  => $isUpdate
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'role_id'   => ['required', Rule::in([Role::BRANCH_ADMIN, Role::EMPLOYEE])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nombre',
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
            'role_id'  => 'rol',
        ];
    }
}
