<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ProductRequest;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\DocumentSeries;
use App\Models\Laboratory;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Unit;
use App\Services\BatchService;
use App\Services\Requirements;
use App\Services\StockService;
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
            ->where('company_id', $employee->company_id)
            ->search($request->buscar, ['code', 'barcode', 'name', 'active_ingredient'])
            // Los nombres coinciden con los campos del formulario de filtros
            ->filterBy(['categoria' => 'category_id', 'laboratorio' => 'laboratory_id', 'estado' => 'status']);

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
        $units        = Unit::deCompania($employee->company_id)->where('status', 1)->orderBy('name')->get();

        // Categoría y unidad son obligatorias en el producto: sin ellas no se puede guardar
        $requirements = Requirements::make('Nuevo producto', 'employee.products.index')
            ->need($categories->isNotEmpty(), 'Categorías', 'Cada producto pertenece a una categoría (analgésicos, antibióticos...).', 'employee.categories.create', 'Registrar categoría')
            ->need($units->isNotEmpty(), 'Unidades de medida', 'Cada producto se vende en una unidad (tableta, frasco, caja...).', 'employee.units.create', 'Registrar unidad');

        if ($requirements->blocked()) {
            return $requirements->view();
        }

        return view('employee.pages.products.form', compact('categories', 'laboratories', 'units'));
    }

    /**
     * Registra el producto con su stock de la sede (y el stock inicial, si se indicó) y sus presentaciones.
     */
    public function store(ProductRequest $request, StockService $stock, BatchService $batches)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            $producto = new Product();
            $producto->company_id             = $employee->company_id;
            $producto->employee_id            = $employee->id;
            // Código interno automático (único en todo el sistema); el de barras lo ingresa el usuario
            $producto->code                   = DocumentSeries::siguiente(DocumentSeries::PRODUCTO);
            $producto->barcode                = $request->codigo_barras ?: null;
            $producto->name                   = $request->nombre;
            $producto->description            = $request->descripcion;
            $producto->category_id            = $request->categoria_id;
            $producto->laboratory_id          = $request->laboratorio_id;
            $producto->active_ingredient      = $request->principio_activo;
            $producto->unit_id                = $request->unidad_medida_id;
            $producto->purchase_price         = $request->precio_compra;
            $producto->unit_sale_price        = $request->precio_venta_unidad;
            $producto->igv_affectation        = $request->afectacion_igv;
            $producto->taxed_product          = $request->afectacion_igv === Product::IGV_GRAVADO;
            $producto->requires_recipe        = $request->has('requiere_receta') || $request->filled('tipo_controlado') ? 1 : 0;
            $producto->controlled_type        = $request->tipo_controlado ?: null;
            $producto->sanitary_registry      = $request->registro_sanitario;
            $producto->save();

            // Crear registro de stock inicial en la sede del empleado
            BranchStock::create([
                'branch_id'     => $employee->branch_id,
                'product_code'  => $producto->code,
                'stock_actual'  => 0,
                'stock_minimum' => $request->stock_minimo ?? null,
                'stock_maximum' => $request->stock_maximo ?? null,
            ]);

            // Stock inicial (mercadería que ya está en el estante): entra al kardex como "Inventario inicial"
            $initialStock = round((float) $request->stock_inicial, 2);
            if ($initialStock > 0) {
                $stock->move(
                    $employee->company_id, $employee->branch_id, $producto->code, $initialStock,
                    'initial', null, (float) $producto->purchase_price, 'Inventario inicial'
                );

                // Con lote o vencimiento se registra como lote (FEFO); si no, quedan como unidades sin lote
                if ($request->filled('lote_inicial') || $request->filled('vencimiento_inicial')) {
                    $batches->receive(
                        $employee->company_id, $employee->branch_id, $producto->code, $initialStock,
                        $request->lote_inicial, $request->vencimiento_inicial, (float) $producto->purchase_price, 'initial', null
                    );
                }
            }

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
                ->with('success', "Producto creado correctamente con el código {$producto->code}."
                    . ($initialStock > 0 ? " Stock inicial: {$initialStock}." : ''));
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
        $units        = Unit::deCompania($employee->company_id)->where('status', 1)->orderBy('name')->get();

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
            $product->barcode                = $request->codigo_barras ?: null;
            $product->name                   = $request->nombre;
            $product->description            = $request->descripcion;
            $product->category_id            = $request->categoria_id;
            $product->laboratory_id          = $request->laboratorio_id;
            $product->active_ingredient      = $request->principio_activo;
            $product->unit_id                = $request->unidad_medida_id;
            $product->purchase_price         = $request->precio_compra;
            $product->unit_sale_price        = $request->precio_venta_unidad;
            $product->igv_affectation        = $request->afectacion_igv;
            $product->taxed_product          = $request->afectacion_igv === Product::IGV_GRAVADO;
            $product->requires_recipe        = $request->has('requiere_receta') || $request->filled('tipo_controlado') ? 1 : 0;
            $product->controlled_type        = $request->tipo_controlado ?: null;
            $product->sanitary_registry      = $request->registro_sanitario;
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

            // Se queda en la edición para que se vea lo que se guardó
            return redirect()->route('employee.products.edit', $product)
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
