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
     * Muestra el kardex de la sede del empleado en un rango de fechas
     * (por defecto el mes actual). Si se pasa ?producto=CODE filtra por ese producto.
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

        // Rango de fechas: por defecto del 1er al último día del mes actual
        $fechaDesde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $fechaHasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        // Las fechas siempre aplican; el producto es un filtro opcional
        $query = StockMovement::with('product:code,name')
            ->where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $fechaDesde)
            ->whereDate('created_at', '<=', $fechaHasta)
            ->orderBy('created_at', 'asc');

        $producto = null;

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

                $query->where('product_code', $producto->code);
            }
        }

        $movimientos = $query->get();

        return view('employee.pages.kardex.index', compact('productos', 'producto', 'movimientos', 'fechaDesde', 'fechaHasta'));
    }
}
