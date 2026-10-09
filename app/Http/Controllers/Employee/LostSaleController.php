<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\LostSaleRequest;
use App\Models\LostSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LostSaleController extends Controller
{
    /**
     * Ventas perdidas de la sede (por defecto del mes) y los productos más pedidos que no se vendieron.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $desde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        $base = LostSale::where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);

        $lostSales = (clone $base)->with('employee:id,name')->orderByDesc('id')->paginate(15)->withQueryString();

        // Ranking: lo que más se pidió y no se pudo vender
        $ranking = (clone $base)
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as times'))
            ->groupBy('product_name')
            ->orderByDesc('times')->orderByDesc('total_qty')
            ->limit(8)->get();

        $products = Product::where('company_id', $employee->company_id)->where('status', 1)
            ->orderBy('name')->get(['code', 'name'])->map(fn ($p) => ['code' => $p->code, 'name' => $p->name])->all();

        return view('employee.pages.lost-sales.index', compact('lostSales', 'ranking', 'products', 'desde', 'hasta'));
    }

    /**
     * Registra lo que el cliente pidió y no se pudo vender.
     */
    public function store(LostSaleRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        LostSale::create([
            'company_id'   => $employee->company_id,
            'branch_id'    => $employee->branch_id,
            'employee_id'  => $employee->id,
            'product_code' => $request->product_code ?: null,
            'product_name' => $request->product_name,
            'quantity'     => $request->quantity,
            'reason'       => $request->reason,
            'notes'        => $request->notes,
        ]);

        return redirect()->route('employee.lost-sales.index')->with('success', 'Venta perdida registrada.');
    }
}
