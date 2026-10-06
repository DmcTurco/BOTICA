<?php

namespace App\Http\Requests\Employee;

use App\Models\CreditNote;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditNoteRequest extends FormRequest
{
    /**
     * Solo el personal con el privilegio de crear notas de crédito
     * (la ruta ya lo exige; aquí se vuelve a comprobar).
     */
    public function authorize(): bool
    {
        return (bool) auth()->guard('employee')->user()?->hasPrivilege(Employee::PRIV_CREAR_NOTA_CREDITO);
    }

    /**
     * Reglas de validación de la nota de crédito.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::in(array_keys(CreditNote::REASONS))],
            'reason_text' => 'required|string|max:250',
        ];
    }

    /**
     * Mensajes de validación en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason_code.required' => 'Selecciona el motivo de la nota de crédito.',
            'reason_code.in'       => 'El motivo seleccionado no es válido.',
            'reason_text.required' => 'Describe el motivo de la anulación.',
            'reason_text.max'      => 'La descripción no puede pasar de 250 caracteres.',
        ];
    }
}
