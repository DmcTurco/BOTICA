<?php

namespace App\Http\Requests\Employee;

use App\Models\CreditNote;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Datos para corregir un comprobante: tipo (boleta o factura) y datos del cliente.
 * La factura exige RUC, nombre y número; la boleta acepta DNI u otro documento (o ninguno).
 */
class VoucherCorrectionRequest extends FormRequest
{
    /**
     * Solo quien puede crear notas de crédito (la ruta ya lo exige; aquí se vuelve a comprobar).
     */
    public function authorize(): bool
    {
        return (bool) auth()->guard('employee')->user()?->hasPrivilege(Employee::PRIV_CREAR_NOTA_CREDITO);
    }

    /**
     * Reglas de validación de la corrección.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isInvoice = (int) $this->input('voucher_type') === 2;
        // Un comprobante aceptado se anula con nota de crédito (pide motivo); uno rechazado no
        $needsNote = $this->route('order')?->sunat_status === Order::SUNAT_ACCEPTED;

        return [
            'voucher_type'      => ['required', Rule::in([1, 2])],
            'document_type_id'  => ['nullable', 'integer', 'exists:document_types,id', Rule::requiredIf($isInvoice)],
            'customer_document' => ['nullable', 'string', 'max:20', Rule::requiredIf($isInvoice)],
            'customer_name'     => ['nullable', 'string', 'max:150', Rule::requiredIf($isInvoice)],
            'reason_code'       => [Rule::requiredIf($needsNote), 'nullable', Rule::in(array_keys(CreditNote::REASONS))],
            'reason_text'       => [Rule::requiredIf($needsNote), 'nullable', 'string', 'max:250'],
        ];
    }

    /**
     * Reglas que dependen de varios campos: factura solo con RUC y largo del documento según su tipo.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $typeId = (int) $this->input('document_type_id');

            if ((int) $this->input('voucher_type') === 2 && $typeId !== DocumentType::RUC) {
                $validator->errors()->add('document_type_id', 'La factura requiere un cliente con RUC. Para un cliente con DNI emite una boleta.');

                return;
            }

            $digits   = $typeId ? DocumentType::find($typeId)?->digits : null;
            $document = (string) $this->input('customer_document');

            if ($digits && $document !== '' && !preg_match('/^\d{' . (int) $digits . '}$/', $document)) {
                $validator->errors()->add('customer_document', "El número de documento debe tener {$digits} dígitos.");
            }
        });
    }

    /**
     * Mensajes de validación en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voucher_type.required'      => 'Elige si el comprobante nuevo es boleta o factura.',
            'voucher_type.in'            => 'El tipo de comprobante no es válido.',
            'document_type_id.required'  => 'La factura requiere el tipo de documento (RUC).',
            'document_type_id.exists'    => 'El tipo de documento no es válido.',
            'customer_document.required' => 'La factura requiere el número de RUC del cliente.',
            'customer_document.max'      => 'El número de documento es demasiado largo.',
            'customer_name.required'     => 'La factura requiere el nombre o la razón social del cliente.',
            'customer_name.max'          => 'El nombre no puede pasar de 150 caracteres.',
            'reason_code.required'       => 'Selecciona el motivo de la nota de crédito.',
            'reason_code.in'             => 'El motivo seleccionado no es válido.',
            'reason_text.required'       => 'Describe el motivo de la corrección.',
            'reason_text.max'            => 'La descripción no puede pasar de 250 caracteres.',
        ];
    }
}
