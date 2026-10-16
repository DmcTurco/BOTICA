<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear o actualizar un producto.
     * Cuando la ruta trae {product} es update; si no, es store.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product   = $this->route('product'); // null en store, modelo en update
        $companyId = auth()->guard('employee')->user()?->company_id;

        // Categoría, laboratorio y unidades deben ser de la propia compañía
        $ofCompany = fn (string $table) => \Illuminate\Validation\Rule::exists($table, 'id')->where('company_id', $companyId);

        $rules = [
            'nombre'              => 'required|max:150',
            'descripcion'         => 'nullable|max:255',
            'categoria_id'        => ['required', $ofCompany('categories')],
            'laboratorio_id'      => ['nullable', $ofCompany('laboratories')],
            'principio_activo'    => 'nullable|max:100',
            'tipo_controlado'     => 'nullable|in:psicotropico,estupefaciente',
            'registro_sanitario'  => 'nullable|max:30',
            'unidad_medida_id'    => ['required', $ofCompany('units')],
            'afectacion_igv'      => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Product::IGV_LABELS))],
            'precio_compra'       => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'stock_minimo'                        => 'nullable|integer|min:0',
            'stock_maximo'                        => 'nullable|integer|min:0',
            'presentaciones.*.unidad_medida_id'   => ['required', $ofCompany('units')],
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta'       => 'required|numeric|min:0',
        ];

        // Stock inicial: solo al crear (mercadería que ya está en el estante). Después el stock
        // entra por compras o por ajustes de inventario.
        if (!$product) {
            $rules['stock_inicial']       = 'nullable|numeric|min:0|max:9999999';
            $rules['lote_inicial']        = 'nullable|max:50';
            $rules['vencimiento_inicial'] = 'nullable|date';
        }

        // El código interno lo genera el sistema (P-000001). El de barras es opcional y no se repite
        // dentro de la compañía (incluye productos eliminados: el índice único de la BD también los cuenta)
        $rules['codigo_barras'] = [
            'nullable', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/',
            \Illuminate\Validation\Rule::unique('products', 'barcode')->where('company_id', $companyId)->ignore($product?->code, 'code'),
        ];

        return $rules;
    }

    /**
     * Nombres legibles de los campos para el modal de alerta.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'presentaciones.*.unidad_medida_id'     => 'unidad de la presentación',
            'presentaciones.*.cantidad_equivalente' => 'cantidad equivalente de la presentación',
            'presentaciones.*.precio_venta'         => 'precio de venta de la presentación',
            'stock_inicial'                         => 'stock inicial',
            'lote_inicial'                          => 'lote del stock inicial',
            'vencimiento_inicial'                   => 'vencimiento del stock inicial',
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
            'codigo_barras.unique'         => 'Ya tienes un producto con ese código de barras.',
            'codigo_barras.regex'          => 'El código de barras solo puede tener letras, números y guiones.',
            'codigo_barras.max'            => 'El código de barras no puede pasar de 30 caracteres.',
            'nombre.required'              => 'El nombre del producto es obligatorio.',
            'categoria_id.required'        => 'Debe seleccionar una categoría.',
            'categoria_id.exists'          => 'La categoría seleccionada no existe.',
            'unidad_medida_id.required'    => 'Debe seleccionar la unidad base.',
            'afectacion_igv.required'      => 'Debe seleccionar la afectación del IGV.',
            'afectacion_igv.in'            => 'La afectación del IGV no es válida.',
            'precio_compra.required'       => 'El precio de compra es obligatorio.',
            'precio_venta_unidad.required' => 'El precio de venta por unidad es obligatorio.',
        ];
    }
}
