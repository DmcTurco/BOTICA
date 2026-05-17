<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employee = auth()->guard('employee')->user();

        // Total de productos activos de la compañía
        $totalProducts = Product::where('company_id', $employee->company_id)
            ->where('status', 1)
            ->count();

        // Productos con stock por debajo del mínimo en la sede del empleado
        $lowStock = BranchStock::where('branch_id', $employee->branch_id)
            ->whereNotNull('stock_minimum')
            ->where('stock_minimum', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimum')
            ->count();

        // Total facturado hoy en la sede del empleado
        $todaySales = Order::where('branch_id', $employee->branch_id)
            ->whereDate('created_at', today())
            ->sum('total') ?? 0;

        // Total facturado en el mes actual en la sede del empleado
        $monthSales = Order::where('branch_id', $employee->branch_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0;

        // Últimas órdenes de la sede para el dashboard
        $recentOrders = Order::where('branch_id', $employee->branch_id)
            ->latest()
            ->take(6)
            ->get();

        return view('employee.pages.home', compact(
            'totalProducts',
            'lowStock',
            'todaySales',
            'monthSales',
            'recentOrders'
        ));
    }
}
