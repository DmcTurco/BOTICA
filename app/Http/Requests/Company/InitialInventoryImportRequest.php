<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Importación del inventario inicial: la plantilla descargada (CSV) llena en Excel,
 * guardada como .xlsx o .csv.
 */
class InitialInventoryImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('employee')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:5120|mimes:xlsx,csv,txt',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Elige el archivo de Excel con el conteo.',
            'file.mimes'    => 'El archivo debe ser Excel (.xlsx) o CSV (.csv).',
            'file.max'      => 'El archivo no puede pesar más de 5 MB.',
        ];
    }
}
