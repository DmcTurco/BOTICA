<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        $categorias = Category::where('status', 1)->get();
        $laboratorios = Laboratory::where('status', 1)->get();
        $presentaciones = Presentation::where('status', 1)->get();
        $unidades = Unit::where('status', 1)->get();
        return view('company.pages.products.index', compact('categorias', 'laboratorios', 'presentaciones', 'unidades'));
    }

    public function create()
    {
        $categorias = Category::where('status', 1)->get();
        $laboratorios = Laboratory::where('status', 1)->get();
        $presentaciones = Presentation::where('status', 1)->get();
        $unidades = Unit::where('status', 1)->get();
        return view('company.pages.products.form', compact('categorias', 'laboratorios', 'presentaciones', 'unidades'));
    }

    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'codigo' => 'required|max:20|unique:productos,codigo',
            'nombre' => 'required|max:150',
            'descripcion' => 'nullable|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'laboratorio_id' => 'nullable|exists:laboratorios,id',
            'principio_activo' => 'nullable|max:100',
            'unidad_medida_id' => 'required|exists:unidades_medida,id',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'precio_compra_paquete' => 'nullable|numeric|min:0',
            'precio_venta_paquete' => 'nullable|numeric|min:0',
            'unidades_por_paquete' => 'nullable|numeric|min:0',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'stock_maximo' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'producto_gravado' => 'nullable|boolean',
            'requiere_receta' => 'nullable|boolean',
            'presentaciones' => 'nullable|array',
            'presentaciones.*.unidad_medida_id' => 'required|exists:unidades_medida,id',
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta' => 'required|numeric|min:0',
            'presentaciones.*.es_presentacion_principal' => 'nullable|boolean',
        ]);

        // Iniciar transacción para garantizar la integridad de los datos
        DB::beginTransaction();

        try {
            // Crear el producto
            $producto = new Product();
            $producto->codigo = $request->codigo;
            $producto->nombre = $request->nombre;
            $producto->descripcion = $request->descripcion;
            $producto->categoria_id = $request->categoria_id;
            $producto->laboratorio_id = $request->laboratorio_id;
            $producto->principio_activo = $request->principio_activo;
            $producto->unidad_medida_id = $request->unidad_medida_id;
            $producto->precio_compra = $request->precio_compra;
            $producto->precio_venta_unidad = $request->precio_venta_unidad;
            $producto->precio_compra_paquete = $request->precio_compra_paquete;
            $producto->precio_venta_paquete = $request->precio_venta_paquete;
            $producto->unidades_por_paquete = $request->unidades_por_paquete;
            $producto->stock_actual = $request->stock_actual;
            $producto->stock_minimo = $request->stock_minimo;
            $producto->stock_maximo = $request->stock_maximo;
            $producto->fecha_vencimiento = $request->fecha_vencimiento;
            $producto->producto_gravado = $request->has('producto_gravado') ? 1 : 0;
            $producto->requiere_receta = $request->has('requiere_receta') ? 1 : 0;

            $producto->save();

            // Guardar presentaciones si existen
            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                foreach ($request->presentaciones as $presentacionData) {
                    // Verificar que los datos necesarios estén presentes
                    if (
                        isset($presentacionData['unidad_medida_id']) &&
                        isset($presentacionData['cantidad_equivalente']) &&
                        isset($presentacionData['precio_venta'])
                    ) {
                        $presentacion = new Presentation();
                        $presentacion->producto_id = $producto->id;
                        $presentacion->unidad_medida_id = $presentacionData['unidad_medida_id'];
                        $presentacion->cantidad_equivalente = $presentacionData['cantidad_equivalente'];
                        $presentacion->precio_venta = $presentacionData['precio_venta'];
                        $presentacion->es_presentacion_principal = isset($presentacionData['es_presentacion_principal']) ? 1 : 0;

                        $presentacion->save();
                    }
                }
            }

            // Confirmar transacción
            DB::commit();

            // Redirigir con mensaje de éxito
            return redirect()->route('productos.index')
                ->with('success', 'Producto creado correctamente.');
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el producto. Por favor, inténtelo de nuevo.');
        }
    }


    public function update(Request $request, $codigo)
    {
        // Buscar el producto por código
        $producto = Product::where('codigo', $codigo)->firstOrFail();

        // Validar los datos del formulario
        // Nota: excluimos el código actual de la validación unique
        $validatedData = $request->validate([
            'nombre' => 'required|max:150',
            'descripcion' => 'nullable|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'laboratorio_id' => 'nullable|exists:laboratorios,id',
            'principio_activo' => 'nullable|max:100',
            'unidad_medida_id' => 'required|exists:unidades_medida,id',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'precio_compra_paquete' => 'nullable|numeric|min:0',
            'precio_venta_paquete' => 'nullable|numeric|min:0',
            'unidades_por_paquete' => 'nullable|numeric|min:0',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'stock_maximo' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'producto_gravado' => 'nullable|boolean',
            'requiere_receta' => 'nullable|boolean',
            'presentaciones' => 'nullable|array',
            'presentaciones.*.unidad_medida_id' => 'required|exists:unidades_medida,id',
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta' => 'required|numeric|min:0',
            'presentaciones.*.es_presentacion_principal' => 'nullable|boolean',
        ]);

        // Iniciar transacción para garantizar la integridad de los datos
        DB::beginTransaction();

        try {
            // Actualizar los datos del producto
            $producto->nombre = $request->nombre;
            $producto->descripcion = $request->descripcion;
            $producto->categoria_id = $request->categoria_id;
            $producto->laboratorio_id = $request->laboratorio_id;
            $producto->principio_activo = $request->principio_activo;
            $producto->unidad_medida_id = $request->unidad_medida_id;
            $producto->precio_compra = $request->precio_compra;
            $producto->precio_venta_unidad = $request->precio_venta_unidad;
            $producto->precio_compra_paquete = $request->precio_compra_paquete;
            $producto->precio_venta_paquete = $request->precio_venta_paquete;
            $producto->unidades_por_paquete = $request->unidades_por_paquete;
            $producto->stock_actual = $request->stock_actual;
            $producto->stock_minimo = $request->stock_minimo;
            $producto->stock_maximo = $request->stock_maximo;
            $producto->fecha_vencimiento = $request->fecha_vencimiento;
            $producto->producto_gravado = $request->has('producto_gravado') ? 1 : 0;
            $producto->requiere_receta = $request->has('requiere_receta') ? 1 : 0;

            $producto->save();

            // Eliminar presentaciones existentes
            $producto->presentaciones()->delete();

            // Guardar presentaciones nuevas si existen
            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                foreach ($request->presentaciones as $presentacionData) {
                    // Verificar que los datos necesarios estén presentes
                    if (
                        isset($presentacionData['unidad_medida_id']) &&
                        isset($presentacionData['cantidad_equivalente']) &&
                        isset($presentacionData['precio_venta'])
                    ) {
                        $presentacion = new Product();
                        $presentacion->producto_id = $producto->id;
                        $presentacion->unidad_medida_id = $presentacionData['unidad_medida_id'];
                        $presentacion->cantidad_equivalente = $presentacionData['cantidad_equivalente'];
                        $presentacion->precio_venta = $presentacionData['precio_venta'];
                        $presentacion->es_presentacion_principal = isset($presentacionData['es_presentacion_principal']) ? 1 : 0;

                        $presentacion->save();
                    }
                }
            }

            // Confirmar transacción
            DB::commit();

            // Redirigir con mensaje de éxito
            return redirect()->route('productos.index')
                ->with('success', 'Producto actualizado correctamente.');
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el producto. Por favor, inténtelo de nuevo.');
        }
    }
}
