<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Laboratory;
use Illuminate\Http\Request;

class ReplenishmentController extends Controller
{
    /**
     * Productos que hay que reponer: stock actual igual o por debajo del mínimo en la sede.
     * La cantidad sugerida llega al stock máximo, o al doble del mínimo si no hay máximo.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = BranchStock::with(['product' => fn ($q) => $q->with(['category:id,name', 'laboratory:id,name', 'unit:id,abbreviation'])])
            ->where('branch_id', $employee->branch_id)
            ->whereNotNull('stock_minimum')
            ->whereColumn('stock_actual', '<=', 'stock_minimum')
            ->whereHas('product', function ($q) use ($employee, $request) {
                $q->where('company_id', $employee->company_id)->where('status', 1);

                if ($request->filled('category')) {
                    $q->where('category_id', $request->category);
                }
                if ($request->filled('laboratory')) {
                    $q->where('laboratory_id', $request->laboratory);
                }
                if ($request->filled('buscar')) {
                    $q->where(fn ($w) => $w->where('name', 'like', '%' . $request->buscar . '%')
                        ->orWhere('code', 'like', '%' . $request->buscar . '%'));
                }
            });

        // Los agotados primero, luego los más críticos respecto a su mínimo
        $rows = $query->orderBy('stock_actual')->paginate(20)->withQueryString();

        $rows->getCollection()->transform(function ($row) {
            $target = $row->stock_maximum ?: ($row->stock_minimum * 2);
            $row->suggested = max(0, round($target - (float) $row->stock_actual, 2));
            return $row;
        });

        return view('employee.pages.replenishment.index', [
            'rows'         => $rows,
            'categories'   => Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
