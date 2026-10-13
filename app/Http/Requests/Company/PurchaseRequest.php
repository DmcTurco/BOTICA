<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar una compra.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('employee')->user()->company_id;

        return [
            'supplier_id'                => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'payment_condition'          => 'nullable|in:cash,credit',
            'due_date'                   => 'nullable|required_if:payment_condition,credit|date|after_or_equal:purchased_at',
            'purchase_order_id'          => ['nullable', Rule::exists('purchase_orders', 'id')->where('company_id', $companyId)->where('status', 'pending')],
            'document_type'              => 'required|in:1,2,3',
            // Boleta y factura necesitan su número; la nota de ingreso es interna
            'document_number'            => ['nullable', 'required_unless:document_type,3', 'max:30', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'supplier'                   => 'nullable|max:150',
            'tax'                        => 'nullable|numeric|min:0',
            'notes'                      => 'nullable|max:500',
            'purchased_at'               => 'required|date|before_or_equal:today',

            // Detalle: al menos un ítem
            'items'                      => 'required|array|min:1',
            'items.*.product_code'       => ['required', Rule::exists('products', 'code')->where('company_id', $companyId)],
            'items.*.presentation_id'    => 'nullable|integer',
            'items.*.quantity'           => 'required|integer|min:1|max:1000000',
            'items.*.unit_cost'          => 'required|numeric|gt:0|max:99999999',
            'items.*.expiration_date'    => 'nullable|date|after:today',
            'items.*.batch'              => 'nullable|max:30',
        ];
    }

    /**
     * Validaciones que cruzan varios campos: presentación del producto, crédito y documento repetido.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyId = auth()->guard('employee')->user()->company_id;

            // La presentación elegida debe ser una presentación activa de ese producto
            foreach ((array) $this->input('items', []) as $i => $item) {
                if (empty($item['presentation_id'])) {
                    continue;
                }
                $valid = \App\Models\Presentation::where('id', $item['presentation_id'])
                    ->where('product_code', $item['product_code'] ?? '')
                    ->where('status', 1)
                    ->exists();
                if (!$valid) {
                    $validator->errors()->add("items.$i.presentation_id", 'La presentación elegida no corresponde al producto (fila ' . ($i + 1) . ').');
                }
            }

            // Una compra a crédito necesita proveedor para poder llevar la deuda
            if ($this->input('payment_condition') === 'credit' && !$this->filled('supplier_id') && !$this->filled('supplier')) {
                $validator->errors()->add('supplier', 'Para una compra a crédito indica el proveedor.');
            }

            // Evitar registrar dos veces el mismo comprobante del mismo proveedor
            if ($this->filled('document_number') && !$validator->errors()->has('document_number')) {
                $exists = \App\Models\Purchase::where('company_id', $companyId)
                    ->where('status', 1)
                    ->where('document_type', $this->input('document_type'))
                    ->where('document_number', trim($this->input('document_number')))
                    ->when($this->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $this->input('supplier_id')))
                    ->when(!$this->filled('supplier_id') && $this->filled('supplier'), fn ($q) => $q->whereRaw('LOWER(supplier) = ?', [mb_strtolower(trim($this->input('supplier')))]))
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('document_number', 'Ya registraste una compra con ese tipo y número de documento para este proveedor.');
                }
            }
        });
    }

    /**
     * Nombres legibles de los campos para el modal de alerta.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_number'         => 'número de documento',
            'purchased_at'            => 'fecha',
            'due_date'                => 'fecha límite de pago',
            'supplier'                => 'proveedor',
            'tax'                     => 'IGV / impuesto',
            'items.*.product_code'    => 'producto',
            'items.*.quantity'        => 'cantidad',
            'items.*.unit_cost'       => 'costo',
            'items.*.expiration_date' => 'vencimiento',
            'items.*.batch'           => 'lote',
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
            'document_type.required'         => 'Debe seleccionar el tipo de documento.',
            'document_type.in'               => 'Tipo de documento no válido.',
            'document_number.required_unless' => 'Ingresa el número de la boleta o factura (ej: F001-00123).',
            'document_number.regex'          => 'El número de documento solo puede tener letras, números y guiones.',
            'purchased_at.required'          => 'La fecha de compra es obligatoria.',
            'purchased_at.before_or_equal'   => 'La fecha de compra no puede ser futura.',
            'items.*.quantity.integer'       => 'La cantidad debe ser un número entero.',
            'items.*.unit_cost.gt'           => 'El costo debe ser mayor a cero.',
            'items.*.expiration_date.after'  => 'El vencimiento debe ser posterior a hoy; no se puede ingresar un producto vencido.',
            'due_date.required_if'           => 'Indica hasta cuándo se debe pagar la compra a crédito.',
            'due_date.after_or_equal'        => 'El vencimiento no puede ser anterior a la fecha de compra.',
            'purchase_order_id.exists'       => 'La orden de compra no existe o ya fue recibida.',
            'items.required'                 => 'Debe agregar al menos un producto.',
            'items.min'                      => 'Debe agregar al menos un producto.',
            'items.*.product_code.required'  => 'Seleccione un producto.',
            'items.*.product_code.exists'    => 'El producto seleccionado no existe.',
            'items.*.quantity.required'      => 'La cantidad es obligatoria.',
            'items.*.quantity.min'           => 'La cantidad debe ser al menos 1.',
            'items.*.unit_cost.required'     => 'El costo unitario es obligatorio.',
            'items.*.unit_cost.min'          => 'El costo no puede ser negativo.',
        ];
    }
}
