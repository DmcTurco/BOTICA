<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaboratoryRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear o actualizar un laboratorio.
     * Cuando la ruta trae {laboratory} es update; si no, es store.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('laboratory') ?? $this->route('id');

        return [
            'nombre'      => ['required', 'max:100', Rule::unique('laboratories', 'name')->ignore($id)],
            'descripcion' => 'nullable|max:255',
            'pais'        => 'nullable|max:80',
            'telefono'    => 'nullable|max:30',
            'email'       => 'nullable|email|max:100',
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
            'nombre.required' => 'El nombre del laboratorio es obligatorio.',
            'nombre.unique'   => 'Ya existe un laboratorio con ese nombre.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'email.email'     => 'Ingrese un correo electrónico válido.',
        ];
    }
}
