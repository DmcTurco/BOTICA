<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    /**
     * Muestra el kardex de un producto seleccionado.
     * Si no se pasa ?producto=CODE, muestra solo el selector.
     */
    public function index(Request $request)
    {
        // Lista de productos para el selector
        $productos = Product::orderBy('came')->get(['code', 'came', 'stock_actual']);

        $producto   = null;
        $movimientos = collect();

        if ($request->filled('producto')) {
            $producto = Product::where('code', $request->producto)->first();

            if ($producto) {
                $query = StockMovement::where('product_code', $producto->code)
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

        return view('company.pages.kardex.index', compact('productos', 'producto', 'movimientos'));
    }
}
