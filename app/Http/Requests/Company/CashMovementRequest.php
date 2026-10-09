<?php

namespace App\Http\Requests\Company;

use App\Models\CashMovement;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashMovementRequest extends FormRequest
{
    /**
     * Cada tipo de movimiento exige su propio privilegio:
     * gastos → gastos_dia · otros ingresos → otros_ingresos.
     */
    public function authorize(): bool
    {
        $employee = auth()->guard('employee')->user();

        return match ($this->input('type')) {
            CashMovement::EXPENSE => $employee->hasPrivilege(Employee::PRIV_GASTOS_DIA),
            CashMovement::INCOME  => $employee->hasPrivilege(Employee::PRIV_OTROS_INGRESOS),
            default               => true, // el tipo inválido lo rechaza rules()
        };
    }

    /**
     * Reglas de validación para registrar un gasto u otro ingreso de caja.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type'    => ['required', Rule::in([CashMovement::INCOME, CashMovement::EXPENSE])],
            'concept' => 'required|string|max:150',
            'amount'  => 'required|numeric|min:0.01|max:99999999',
            'notes'   => 'nullable|string|max:500',
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
            'type.required'    => 'Selecciona si es un gasto o un ingreso.',
            'concept.required' => 'Indica el concepto del movimiento.',
            'amount.required'  => 'Indica el monto.',
            'amount.min'       => 'El monto debe ser mayor a 0.',
        ];
    }
}
