<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;

class AdminController extends Controller
{
    /**
     * Panel del administrador: totales de todo el sistema.
     */
    public function index()
    {
        $stats = [
            'companies' => Company::count(),
            'employees' => Employee::count(),
            'products'  => Product::count(),
            // Ventas válidas del mes en curso (status 1), de todas las compañías
            'salesMonth' => (float) Order::where('status', 1)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
        ];

        return view('admin.pages.home', compact('stats'));
    }
}
