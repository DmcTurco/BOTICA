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
    public function index(Request $request)
    {
        $query = Product::with(['categoria', 'laboratorio', 'unidadMedida']);

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->buscar . '%')
                  ->orWhere('came', 'like', '%' . $request->buscar . '%')
                  ->orWhere('active_ingredient', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('categoria')) {
            $query->where('category_id', $request->categoria);
        }

        if ($request->filled('laboratorio')) {
            $query->where('laboratory_id', $request->laboratorio);
        }

        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        $productos    = $query->orderBy('came')->paginate(9)->withQueryString();
        $categorias   = Category::where('status', 1)->orderBy('name')->get();
        $laboratorios = Laboratory::where('status', 1)->orderBy('name')->get();

        return view('company.pages.products.index', compact('productos', 'categorias', 'laboratorios'));
    }

    public function create()
    {
        $categorias     = Category::where('status', 1)->get();
        $laboratorios   = Laboratory::where('status', 1)->get();
        $presentaciones = Presentation::where('status', 1)->get();
        $unidades       = Unit::where('status', 1)->get();
        return view('company.pages.products.form', compact('categorias', 'laboratorios', 'presentaciones', 'unidades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'              => 'required|max:20|unique:products,code',
            'nombre'              => 'required|max:150',
            'descripcion'         => 'nullable|max:255',
            'categoria_id'        => 'required|exists:categories,id',
            'laboratorio_id'      => 'nullable|exists:laboratories,id',
            'principio_activo'    => 'nullable|max:100',
            'unidad_medida_id'    => 'required|exists:units,id',
            'precio_compra'       => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'precio_compra_paquete'   => 'nullable|numeric|min:0',
            'precio_venta_paquete'    => 'nullable|numeric|min:0',
            'unidades_por_paquete'    => 'nullable|numeric|min:0',
            'stock_actual'        => 'required|numeric|min:0',
            'stock_minimo'        => 'nullable|numeric|min:0',
            'stock_maximo'        => 'nullable|numeric|min:0',
            'fecha_vencimiento'   => 'nullable|date',
            'presentaciones.*.unidad_medida_id'     => 'required|exists:units,id',
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta'         => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $producto = new Product();
            $producto->code                   = $request->codigo;
            $producto->came                   = $request->nombre;
            $producto->description            = $request->descripcion;
            $producto->category_id            = $request->categoria_id;
            $producto->laboratory_id          = $request->laboratorio_id;
            $producto->active_ingredient      = $request->principio_activo;
            $producto->unit_id                = $request->unidad_medida_id;
            $producto->purchase_price         = $request->precio_compra;
            $producto->unit_sale_price        = $request->precio_venta_unidad;
            $producto->package_purchase_price = $request->precio_compra_paquete;
            $producto->package_sale_price     = $request->precio_venta_paquete;
            $producto->units_per_package      = $request->unidades_por_paquete;
            $producto->stock_actual           = $request->stock_actual;
            $producto->stock_minimum          = $request->stock_minimo;
            $producto->stock_maximum          = $request->stock_maximo;
            $producto->expiration_date        = $request->fecha_vencimiento;
            $producto->taxed_product          = $request->has('producto_gravado') ? 1 : 0;
            $producto->requires_recipe        = $request->has('requiere_receta') ? 1 : 0;
            $producto->save();

            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                foreach ($request->presentaciones as $data) {
                    if (isset($data['unidad_medida_id'], $data['cantidad_equivalente'], $data['precio_venta'])) {
                        Presentation::create([
                            'product_code'      => $producto->code,
                            'unit_id'           => $data['unidad_medida_id'],
                            'equivalent_amount' => $data['cantidad_equivalente'],
                            'sale_price'        => $data['precio_venta'],
                            'main_presentation' => isset($data['es_presentacion_principal']) ? 1 : 0,
                            'status'            => 1,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('company.products.index')
                ->with('success', 'Producto creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al crear el producto. Por favor, inténtelo de nuevo.');
        }
    }

    public function edit(Product $product)
    {
        $categorias     = Category::where('status', 1)->get();
        $laboratorios   = Laboratory::where('status', 1)->get();
        $presentaciones = Presentation::where('status', 1)->get();
        $unidades       = Unit::where('status', 1)->get();
        $producto       = $product->load('presentaciones.unidadMedida');
        return view('company.pages.products.form', compact('producto', 'categorias', 'laboratorios', 'presentaciones', 'unidades'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'nombre'              => 'required|max:150',
            'descripcion'         => 'nullable|max:255',
            'categoria_id'        => 'required|exists:categories,id',
            'laboratorio_id'      => 'nullable|exists:laboratories,id',
            'principio_activo'    => 'nullable|max:100',
            'unidad_medida_id'    => 'required|exists:units,id',
            'precio_compra'       => 'required|numeric|min:0',
            'precio_venta_unidad' => 'required|numeric|min:0',
            'precio_compra_paquete'   => 'nullable|numeric|min:0',
            'precio_venta_paquete'    => 'nullable|numeric|min:0',
            'unidades_por_paquete'    => 'nullable|numeric|min:0',
            'stock_actual'        => 'required|numeric|min:0',
            'stock_minimo'        => 'nullable|numeric|min:0',
            'stock_maximo'        => 'nullable|numeric|min:0',
            'fecha_vencimiento'   => 'nullable|date',
            'presentaciones.*.unidad_medida_id'     => 'required|exists:units,id',
            'presentaciones.*.cantidad_equivalente' => 'required|numeric|min:0.01',
            'presentaciones.*.precio_venta'         => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $product->came                   = $request->nombre;
            $product->description            = $request->descripcion;
            $product->category_id            = $request->categoria_id;
            $product->laboratory_id          = $request->laboratorio_id;
            $product->active_ingredient      = $request->principio_activo;
            $product->unit_id                = $request->unidad_medida_id;
            $product->purchase_price         = $request->precio_compra;
            $product->unit_sale_price        = $request->precio_venta_unidad;
            $product->package_purchase_price = $request->precio_compra_paquete;
            $product->package_sale_price     = $request->precio_venta_paquete;
            $product->units_per_package      = $request->unidades_por_paquete;
            $product->stock_actual           = $request->stock_actual;
            $product->stock_minimum          = $request->stock_minimo;
            $product->stock_maximum          = $request->stock_maximo;
            $product->expiration_date        = $request->fecha_vencimiento;
            $product->taxed_product          = $request->has('producto_gravado') ? 1 : 0;
            $product->requires_recipe        = $request->has('requiere_receta') ? 1 : 0;
            $product->save();

            $product->presentaciones()->delete();

            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                foreach ($request->presentaciones as $data) {
                    if (isset($data['unidad_medida_id'], $data['cantidad_equivalente'], $data['precio_venta'])) {
                        Presentation::create([
                            'product_code'      => $product->code,
                            'unit_id'           => $data['unidad_medida_id'],
                            'equivalent_amount' => $data['cantidad_equivalente'],
                            'sale_price'        => $data['precio_venta'],
                            'main_presentation' => isset($data['es_presentacion_principal']) ? 1 : 0,
                            'status'            => 1,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('company.products.index')
                ->with('success', 'Producto actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar el producto. Por favor, inténtelo de nuevo.');
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->presentaciones()->delete();
            $product->delete();
            return redirect()->route('company.products.index')
                ->with('success', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return redirect()->route('company.products.index')
                ->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }
}
