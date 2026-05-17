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
        $product = $this->route('product'); // null en store, modelo en update

        $rules = [
            'nombre'              => 'required|max:150',
            'descripcion'         => 'nullable|max:255',
            'categoria_id'        => 'required|exists:categories,id',
            'laboratorio_id'      => 'nullable|exists:laboratories,id',
            'principio_activo'    => 'nullable|max:100',
            'unidad_medida_id'    => 'required|exists:units,id',
            'precio_compra'       => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'precio_compra_paquete'               => 'nullable|numeric|min:0',
            'precio_venta_paquete'                => 'nullable|numeric|min:0',
            'unidades_por_paquete'                => 'nullable|numeric|min:0',
            'stock_minimo'                        => 'nullable|integer|min:0',
            'stock_maximo'                        => 'nullable|integer|min:0',
            'presentaciones.*.unidad_medida_id'   => 'required|exists:units,id',
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta'       => 'required|numeric|min:0',
        ];

        // El código solo se valida en store (en update no se puede cambiar)
        if (!$product) {
            $rules['codigo'] = 'required|max:20|unique:products,code';
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required'              => 'El código del producto es obligatorio.',
            'codigo.unique'                => 'Ya existe un producto con ese código.',
            'nombre.required'              => 'El nombre del producto es obligatorio.',
            'categoria_id.required'        => 'Debe seleccionar una categoría.',
            'categoria_id.exists'          => 'La categoría seleccionada no existe.',
            'unidad_medida_id.required'    => 'Debe seleccionar una unidad de medida.',
            'precio_compra.required'       => 'El precio de compra es obligatorio.',
            'precio_venta_unidad.required' => 'El precio de venta por unidad es obligatorio.',
        ];
    }
}
