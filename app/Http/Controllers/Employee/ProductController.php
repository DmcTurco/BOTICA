<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ProductRequest;
use App\Models\BranchStock;
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
        $employee = auth()->guard('employee')->user();

        $branchId = $employee->branch_id;
        $query = Product::with([
                'category',
                'laboratory',
                'unit',
                'branchStocks' => fn ($q) => $q->where('branch_id', $branchId),
            ])
            ->where('company_id', $employee->company_id);

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->buscar . '%')
                  ->orWhere('name', 'like', '%' . $request->buscar . '%')
                  ->orWhere('active_ingredient', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('laboratory')) {
            $query->where('laboratory_id', $request->laboratory);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products    = $query->orderBy('name')->paginate(9)->withQueryString();
        $categories   = Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();
        $laboratories = Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();

        return view('employee.pages.products.index', compact('products', 'categories', 'laboratories'));
    }

    public function create()
    {
        $employee     = auth()->guard('employee')->user();
        $categories   = Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();
        $laboratories = Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();
        $units        = Unit::where('status', 1)->orderBy('name')->get();

        return view('employee.pages.products.form', compact('categories', 'laboratories', 'units'));
    }

    public function store(ProductRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            $producto = new Product();
            $producto->company_id             = $employee->company_id;
            $producto->employee_id            = $employee->id;
            $producto->code                   = $request->codigo;
            $producto->name                   = $request->nombre;
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
            $producto->taxed_product          = $request->has('producto_gravado') ? 1 : 0;
            $producto->requires_recipe        = $request->has('requiere_receta') ? 1 : 0;
            $producto->save();

            // Crear registro de stock inicial en la sede del empleado
            BranchStock::create([
                'branch_id'     => $employee->branch_id,
                'product_code'  => $producto->code,
                'stock_actual'  => 0,
                'stock_minimum' => $request->stock_minimo ?? null,
                'stock_maximum' => $request->stock_maximo ?? null,
            ]);

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

            return redirect()->route('employee.products.index')
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
        $employee = auth()->guard('employee')->user();

        // Verificar que el producto pertenece a la compañía del empleado
        abort_if($product->company_id !== $employee->company_id, 403);

        $categories   = Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();
        $laboratories = Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get();
        $units        = Unit::where('status', 1)->orderBy('name')->get();

        // Cargar presentaciones propias del producto y stock de la sede del empleado
        $products = $product->load([
            'presentations.unit',
            'branchStocks' => fn ($q) => $q->where('branch_id', $employee->branch_id),
        ]);

        return view('employee.pages.products.form', compact('products', 'categories', 'laboratories', 'units'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($product->company_id !== $employee->company_id, 403);

        DB::beginTransaction();

        try {
            $product->name                   = $request->nombre;
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
            $product->taxed_product          = $request->has('producto_gravado') ? 1 : 0;
            $product->requires_recipe        = $request->has('requiere_receta') ? 1 : 0;
            $product->save();

            // Actualizar mínimos/máximos en el branch_stock de la sede del empleado
            BranchStock::where('branch_id', $employee->branch_id)
                ->where('product_code', $product->code)
                ->update([
                    'stock_minimum' => $request->stock_minimo ?? null,
                    'stock_maximum' => $request->stock_maximo ?? null,
                ]);

            $product->presentations()->delete();

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

            return redirect()->route('employee.products.index')
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
        $employee = auth()->guard('employee')->user();

        abort_if($product->company_id !== $employee->company_id, 403);

        try {
            $product->presentations()->delete();
            $product->branchStocks()->delete();
            $product->delete();
            return redirect()->route('employee.products.index')
                ->with('success', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return redirect()->route('employee.products.index')
                ->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }
}
