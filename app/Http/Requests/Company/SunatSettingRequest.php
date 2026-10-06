<?php

namespace App\Http\Requests\Company;

use App\Models\CompanySunatSetting;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SunatSettingRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     * Solo la compañía autenticada edita su propia configuración (las rutas ya exigen auth:company).
     */
    public function authorize(): bool
    {
        return auth()->guard('company')->check();
    }

    /**
     * Reglas de validación de la configuración de facturación electrónica.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = auth()->guard('company')->user();

        return [
            'ruc'            => [
                'required', 'digits:11',
                Rule::unique('companies', 'ruc')->ignore($company->id),
                $this->rucChecksum(),
            ],
            'legal_name'     => 'required|string|max:150',
            'trade_name'     => 'nullable|string|max:150',
            'fiscal_address' => 'required|string|max:200',
            'ubigeo'         => 'required|digits:6',
            'department'     => 'required|string|max:60',
            'province'       => 'required|string|max:60',
            'district'       => 'required|string|max:60',

            'environment'    => ['required', Rule::in([CompanySunatSetting::ENV_BETA, CompanySunatSetting::ENV_PRODUCTION])],
            'enabled'        => 'nullable|boolean',

            // Vacío = conservar lo que ya está guardado
            'sol_user'       => 'nullable|string|max:50',
            'sol_password'   => 'nullable|string|max:100',

            'certificate'          => 'nullable|file|extensions:pfx,p12|max:200',
            'certificate_password' => 'nullable|string|max:100|required_with:certificate',
        ];
    }

    /**
     * Para emitir en producción o dejar activa la emisión, la configuración debe estar completa.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // En pruebas (beta) no hacen falta claves SOL ni certificado propios
                if ($this->input('environment') !== CompanySunatSetting::ENV_PRODUCTION) {
                    return;
                }

                // Consulta directa (no la relación cacheada) para ver lo que ya está guardado
                $current = CompanySunatSetting::where('company_id', auth()->guard('company')->id())->first();

                $hasUser = filled($this->input('sol_user')) || filled($current?->sol_user);
                $hasPass = filled($this->input('sol_password')) || filled($current?->sol_password);
                $hasCert = $this->hasFile('certificate') || $current?->hasCertificate();

                if (!$hasUser || !$hasPass) {
                    $validator->errors()->add('sol_user', 'Para usar producción debes ingresar el usuario y la clave SOL.');
                }

                if (!$hasCert) {
                    $validator->errors()->add('certificate', 'Para usar producción debes cargar el certificado digital.');
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
            'ruc.required'                     => 'Ingresa el RUC.',
            'ruc.digits'                       => 'El RUC debe tener 11 dígitos.',
            'ruc.unique'                       => 'Este RUC ya está registrado en otra compañía.',
            'legal_name.required'              => 'Ingresa la razón social.',
            'fiscal_address.required'          => 'Ingresa la dirección fiscal.',
            'ubigeo.required'                  => 'Ingresa el ubigeo.',
            'ubigeo.digits'                    => 'El ubigeo debe tener 6 dígitos.',
            'department.required'              => 'Ingresa el departamento.',
            'province.required'                => 'Ingresa la provincia.',
            'district.required'                => 'Ingresa el distrito.',
            'certificate.extensions'           => 'El certificado debe ser un archivo .pfx o .p12.',
            'certificate.max'                  => 'El certificado no puede pesar más de 200 KB.',
            'certificate_password.required_with' => 'Ingresa la clave del certificado.',
        ];
    }

    /**
     * Valida el dígito verificador del RUC (módulo 11) y su prefijo (10, 15, 17 o 20).
     */
    private function rucChecksum(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $ruc = (string) $value;

            if (strlen($ruc) !== 11 || !ctype_digit($ruc)) {
                return; // ya lo valida la regla digits:11
            }

            if (!in_array(substr($ruc, 0, 2), ['10', '15', '17', '20'], true)) {
                $fail('El RUC debe empezar con 10, 15, 17 o 20.');
                return;
            }

            $weights = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            $sum     = 0;

            foreach ($weights as $i => $weight) {
                $sum += (int) $ruc[$i] * $weight;
            }

            $check = (11 - ($sum % 11)) % 10;

            if ($check !== (int) $ruc[10]) {
                $fail('El RUC no es válido (dígito verificador incorrecto).');
            }
        };
    }
}
