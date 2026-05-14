<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class CompanyController extends Controller
{
    public function index()
    {
        $totalProducts = Product::where('status', 1)->count();

        // Productos con stock por debajo del mínimo definido
        $lowStock = Product::where('status', 1)
            ->where('stock_minimum', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimum')
            ->count();

        // Total facturado hoy
        $todaySales = Order::whereDate('created_at', today())->sum('total') ?? 0;

        // Total facturado en el mes actual
        $monthSales = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0;

        // Últimas órdenes para el dashboard
        $recentOrders = Order::latest()->take(6)->get();

        return view('company.pages.home', compact(
            'totalProducts',
            'lowStock',
            'todaySales',
            'monthSales',
            'recentOrders'
        ));
    }
}
