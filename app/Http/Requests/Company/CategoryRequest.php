<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear o actualizar una categoría.
     * Cuando la ruta trae {category} o el segmento de ID es update; si no, es store.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // CategoryController usa ruta manual con $id (no resource binding)
        $id = $this->route('category') ?? $this->route('id');

        return [
            'nombre'      => ['required', 'max:100', Rule::unique('categories', 'name')->ignore($id)],
            'descripcion' => 'nullable|max:255',
            'icono'       => 'nullable|max:50',
            'activo'      => 'nullable|boolean',
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
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique'   => 'Ya existe una categoría con ese nombre.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
