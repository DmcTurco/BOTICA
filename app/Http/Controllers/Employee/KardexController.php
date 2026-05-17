<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    /**
     * Muestra el kardex de un producto seleccionado, filtrado por sede.
     * Si no se pasa ?producto=CODE, muestra solo el selector.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        // Lista de productos de la compañía con su stock en la sede del empleado
        $productos = Product::where('company_id', $employee->company_id)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(function ($p) use ($employee) {
                $p->stock_actual = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $p->code)
                    ->value('stock_actual') ?? 0;
                return $p;
            });

        $producto    = null;
        $movimientos = collect();

        if ($request->filled('producto')) {
            $producto = Product::where('code', $request->producto)
                ->where('company_id', $employee->company_id)
                ->first();

            if ($producto) {
                // Mapear stock y mínimo desde branch_stock para la vista
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $producto->code)
                    ->first();
                $producto->stock_actual  = $branchStock?->stock_actual ?? 0;
                $producto->stock_minimum = $branchStock?->stock_minimum;
                $query = StockMovement::where('product_code', $producto->code)
                    ->where('branch_id', $employee->branch_id)
                    ->orderBy('created_at', 'asc');

                // Filtro por rango de fechas
                if ($request->filled('fecha_desde')) {
                    $query->whereDate('created_at', '>=', $request->fecha_desde);
                }

                if ($request->filled('fecha_hasta')) {
                    $query->whereDate('created_at', '<=', $request->fecha_hasta);
                }

                $movimientos = $query->get();
            }
        }

        return view('employee.pages.kardex.index', compact('productos', 'producto', 'movimientos'));
    }
}
