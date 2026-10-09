<?php

namespace App\Http\Requests\Company;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormulaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el interruptor "vender en POS" antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sell_in_pos' => $this->boolean('sell_in_pos'),
            // Si no se vende en el POS no se vincula ningún producto
            'product_code' => $this->boolean('sell_in_pos') ? $this->input('product_code') : null,
        ]);
    }

    /**
     * Reglas de validación para crear o editar una fórmula
     * (se detecta la edición por la presencia del parámetro de ruta).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;

        return [
            'name'                => 'required|string|max:150',
            'pharmaceutical_form' => 'nullable|string|max:50',
            'description'         => 'nullable|string|max:1000',
            'yield_quantity'      => 'required|numeric|min:0.01|max:99999999',
            'yield_unit_id'       => 'nullable|exists:units,id',
            'procedure'           => 'nullable|string|max:5000',
            'storage'             => 'nullable|string|max:255',
            'shelf_life_days'     => 'nullable|integer|min:1|max:3650',
            'status'              => 'required|in:0,1',

            // Venta en el POS: exige un producto de la compañía que reciba el stock
            'sell_in_pos'  => 'boolean',
            'product_code' => [
                Rule::requiredIf(fn () => $this->boolean('sell_in_pos')),
                'nullable',
                Rule::exists('products', 'code')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],

            // Insumos: al menos uno, sin repetir producto
            'ingredients'                => 'required|array|min:1',
            'ingredients.*.product_code' => [
                'required',
                'distinct',
                Rule::exists('products', 'code')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'ingredients.*.quantity'     => 'required|numeric|min:0.01|max:99999999',
            'ingredients.*.notes'        => 'nullable|string|max:255',
        ];
    }

    /**
     * El producto final no puede ser a la vez un insumo de la misma fórmula.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $codes = array_column($this->input('ingredients', []), 'product_code');

                if ($this->boolean('sell_in_pos') && in_array($this->input('product_code'), $codes, true)) {
                    $validator->errors()->add('product_code', 'El producto final no puede ser también un insumo de la fórmula.');
                }
            },
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
            'name.required'                       => 'El nombre de la fórmula es obligatorio.',
            'yield_quantity.required'             => 'Indica cuánto rinde la fórmula.',
            'yield_quantity.min'                  => 'El rendimiento debe ser mayor a 0.',
            'product_code.required'               => 'Selecciona el producto que se venderá en el POS.',
            'product_code.exists'                 => 'El producto seleccionado no existe.',
            'ingredients.required'                => 'Agrega al menos un insumo.',
            'ingredients.min'                     => 'Agrega al menos un insumo.',
            'ingredients.*.product_code.required' => 'Selecciona el insumo.',
            'ingredients.*.product_code.distinct' => 'Un insumo está repetido en la fórmula.',
            'ingredients.*.product_code.exists'   => 'Un insumo seleccionado no existe.',
            'ingredients.*.quantity.required'     => 'Indica la cantidad de cada insumo.',
            'ingredients.*.quantity.min'          => 'La cantidad de cada insumo debe ser mayor a 0.',
        ];
    }
}
