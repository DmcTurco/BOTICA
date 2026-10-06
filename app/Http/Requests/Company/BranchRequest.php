<?php

namespace App\Http\Requests\Company;

use App\Models\DocumentSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BranchRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     * Solo la compañía autenticada (las rutas ya exigen auth:company).
     */
    public function authorize(): bool
    {
        return auth()->guard('company')->check();
    }

    /**
     * Las series se guardan siempre en mayúsculas y sin espacios.
     */
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('series'))) {
            $this->merge([
                'series' => array_map(
                    fn ($value) => strtoupper(trim((string) $value)),
                    $this->input('series')
                ),
            ]);
        }
    }

    /**
     * Reglas de validación para crear o actualizar una sede.
     * Cuando la ruta trae {branch} es update; si no, es store.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'                     => 'required|string|max:100',
            'address'                  => 'nullable|string|max:200',
            'phone'                    => 'nullable|string|max:30',
            'email'                    => 'nullable|email|max:100',
            'sunat_establishment_code' => 'required|digits:4',
            'status'                   => 'required|in:0,1',

            // Series de la sede (solo al editar): [id de la serie => texto de la serie]
            'series'                   => 'nullable|array',
            'series.*'                 => 'nullable|string|max:4',
        ];
    }

    /**
     * Valida el formato y la unicidad de las series editadas.
     * Una serie que ya emitió comprobantes no se puede cambiar.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $branch = $this->route('branch');

                if (!$branch || !$this->filled('series')) {
                    return;
                }

                $rows = DocumentSeries::where('branch_id', $branch->id)
                    ->whereIn('type_code', array_keys(DocumentSeries::PER_BRANCH))
                    ->get()
                    ->keyBy('id');

                $seen = [];

                foreach ($this->input('series') as $id => $value) {
                    $row = $rows->get((int) $id);

                    if (!$row || $value === '' || $value === $row->series) {
                        continue;
                    }

                    $label = DocumentSeries::PER_BRANCH[$row->type_code]['name'];

                    if ($row->current_number > 0) {
                        $validator->errors()->add("series.$id", "La serie de {$label} ya emitió comprobantes y no se puede cambiar.");
                        continue;
                    }

                    // Formato según el tipo (SUNAT: boleta B+3, factura F+3)
                    $format = match ($row->type_code) {
                        DocumentSeries::BOLETA, DocumentSeries::NOTA_CREDITO_BOLETA   => '/^B[A-Z0-9]{3}$/',
                        DocumentSeries::FACTURA, DocumentSeries::NOTA_CREDITO_FACTURA => '/^F[A-Z0-9]{3}$/',
                        default                                                       => '/^[A-Z0-9]{2,4}$/',
                    };

                    if (!preg_match($format, $value)) {
                        $hint = match ($row->type_code) {
                            DocumentSeries::BOLETA, DocumentSeries::NOTA_CREDITO_BOLETA   => 'debe tener 4 caracteres y empezar con B (ej. B001)',
                            DocumentSeries::FACTURA, DocumentSeries::NOTA_CREDITO_FACTURA => 'debe tener 4 caracteres y empezar con F (ej. F001)',
                            default                                                       => 'debe tener entre 2 y 4 letras o números (ej. NV01)',
                        };
                        $validator->errors()->add("series.$id", "La serie de {$label} {$hint}.");
                        continue;
                    }

                    // No repetir una serie de la misma compañía y tipo (ni en este mismo envío)
                    $key = $row->type_code . '|' . $value;
                    $taken = isset($seen[$key]) || DocumentSeries::where('company_id', $branch->company_id)
                        ->where('type_code', $row->type_code)
                        ->where('series', $value)
                        ->where('id', '!=', $row->id)
                        ->exists();

                    if ($taken) {
                        $validator->errors()->add("series.$id", "La serie {$value} de {$label} ya está en uso en otra sede.");
                    }

                    $seen[$key] = true;
                }
            },
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
            'name.required'                     => 'Ingresa el nombre de la sede.',
            'email.email'                       => 'Ingresa un email válido.',
            'sunat_establishment_code.required' => 'Ingresa el código de establecimiento SUNAT.',
            'sunat_establishment_code.digits'   => 'El código de establecimiento debe tener 4 dígitos (ej. 0000).',
        ];
    }
}
