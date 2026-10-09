<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar una preparación (lote).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;

        return [
            'formula_id'        => [
                'required',
                Rule::exists('formulas', 'id')->where('company_id', $companyId)->where('status', 1)->whereNull('deleted_at'),
            ],
            'quantity_produced' => 'required|numeric|min:0.01|max:99999999',
            'batch'             => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('productions', 'batch')->where('company_id', $companyId),
            ],
            'produced_at'       => 'required|date|before_or_equal:today',
            'expiration_date'   => 'nullable|date|after:produced_at',
            'notes'             => 'nullable|string|max:500',
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
            'formula_id.required'              => 'Selecciona la fórmula a preparar.',
            'formula_id.exists'                => 'La fórmula no existe o está inactiva.',
            'quantity_produced.required'       => 'Indica la cantidad que vas a preparar.',
            'quantity_produced.min'            => 'La cantidad debe ser mayor a 0.',
            'batch.unique'                     => 'Ya existe una preparación con ese número de lote.',
            'produced_at.required'             => 'La fecha de elaboración es obligatoria.',
            'produced_at.before_or_equal'      => 'La fecha de elaboración no puede ser futura.',
            'expiration_date.after'            => 'El vencimiento debe ser posterior a la fecha de elaboración.',
        ];
    }
}
