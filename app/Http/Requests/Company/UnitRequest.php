<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /** Códigos SUNAT de unidad de medida más usados en farmacia (catálogo 03) */
    const SUNAT_CODES = [
        'NIU' => 'NIU · Unidad',
        'BX'  => 'BX · Caja',
        'BO'  => 'BO · Frasco / botella',
        'TU'  => 'TU · Tubo',
        'PK'  => 'PK · Paquete',
        'BG'  => 'BG · Bolsa',
        'MLT' => 'MLT · Mililitro',
        'LTR' => 'LTR · Litro',
        'GRM' => 'GRM · Gramo',
        'KGM' => 'KGM · Kilogramo',
        'ZZ'  => 'ZZ · Servicio',
    ];

    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear o editar una unidad
     * (se detecta la edición por el parámetro de ruta).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name'         => ['required', 'string', 'max:50', Rule::unique('units', 'name')->ignore($unit?->id)->whereNull('deleted_at')],
            'abbreviation' => ['required', 'string', 'max:10', Rule::unique('units', 'abbreviation')->ignore($unit?->id)->whereNull('deleted_at')],
            'sunat_code'   => ['required', Rule::in(array_keys(self::SUNAT_CODES))],
            'status'       => 'required|in:0,1',
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
            'name.required'         => 'El nombre de la unidad es obligatorio.',
            'name.unique'           => 'Ya existe una unidad con ese nombre.',
            'abbreviation.required' => 'La abreviatura es obligatoria.',
            'abbreviation.unique'   => 'Ya existe una unidad con esa abreviatura.',
            'sunat_code.required'   => 'Selecciona el código SUNAT.',
        ];
    }
}
