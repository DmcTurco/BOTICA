<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales;

class CompanyController extends Controller
{
    public function index()
    {
        $totalProductos = Product::where('status', 1)->count();

        $stockBajo = Product::where('status', 1)
            ->where('stock_minimum', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimum')
            ->count();

        $ventasHoy = Sales::whereDate('created_at', today())->sum('total') ?? 0;

        $ventasMes = Sales::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0;

        $ventasRecientes = Sales::latest()->take(6)->get();

        return view('company.pages.home', compact(
            'totalProductos',
            'stockBajo',
            'ventasHoy',
            'ventasMes',
            'ventasRecientes'
        ));
    }
}
