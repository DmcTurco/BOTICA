<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function index()
    {
        $company = Auth::guard('company')->user();

        // Total de productos activos de la compañía
        $totalProducts = Product::where('company_id', $company->id)
            ->where('status', 1)
            ->count();

        // Productos bajo stock mínimo en cualquier sede de la compañía
        $lowStock = BranchStock::whereHas('branch', fn ($q) => $q->where('company_id', $company->id))
            ->whereNotNull('stock_minimum')
            ->where('stock_minimum', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimum')
            ->count();

        // Total facturado hoy en la compañía
        $todaySales = Order::where('company_id', $company->id)
            ->whereDate('created_at', today())
            ->sum('total') ?? 0;

        // Total facturado en el mes actual en la compañía
        $monthSales = Order::where('company_id', $company->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0;

        // Total de sedes activas de la compañía
        $totalBranches = Branch::where('company_id', $company->id)
            ->where('status', 1)
            ->count();

        // Empleados por sede (solo sedes activas)
        $employeesPerBranch = Branch::where('company_id', $company->id)
            ->where('status', 1)
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        // Total de empleados de la compañía
        $totalEmployees = Employee::where('company_id', $company->id)
            ->count();

        // Cantidad de ventas (transacciones) realizadas hoy
        $todayOrdersCount = Order::where('company_id', $company->id)
            ->whereDate('created_at', today())
            ->count();

        // Últimas órdenes de la compañía para el dashboard
        $recentOrders = Order::where('company_id', $company->id)
            ->latest()
            ->take(6)
            ->get();

        return view('company.pages.home', compact(
            'totalProducts',
            'lowStock',
            'todaySales',
            'monthSales',
            'todayOrdersCount',
            'totalBranches',
            'totalEmployees',
            'employeesPerBranch',
            'recentOrders'
        ));
    }
}
