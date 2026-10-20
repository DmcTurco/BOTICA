<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Subida del archivo de productos de un cliente: el modelo lleno (Excel .xlsx o CSV).
 * La compañía y sede se eligen después, al registrar.
 */
class ClientImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240|mimes:xlsx,csv,txt',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required'      => 'Elige el archivo con los productos del cliente.',
            'file.mimes'         => 'El archivo debe ser Excel (.xlsx) o CSV (.csv).',
            'file.max'           => 'El archivo no puede pesar más de 10 MB.',
        ];
    }
}
