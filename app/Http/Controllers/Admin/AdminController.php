<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Order;
use App\Models\SubscriptionPayment;

class AdminController extends Controller
{
    /**
     * Panel del administrador: totales de todo el sistema.
     */
    public function index()
    {
        // Empresas con la mensualidad vencida (en gracia o bloqueadas), para cobrarles
        $overdue = Company::whereNotNull('paid_until')->where('paid_until', '<', today())
            ->orderBy('paid_until')->get();

        $stats = [
            'companies' => Company::count(),
            // Lo cobrado de mensualidades este mes (según la fecha de pago)
            'subscriptionIncome' => (float) SubscriptionPayment::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'employees' => Employee::count(),
            // Ventas válidas del mes en curso (status 1), de todas las compañías
            'salesMonth' => (float) Order::where('status', 1)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
        ];

        return view('admin.pages.home', compact('stats', 'overdue'));
    }
}
