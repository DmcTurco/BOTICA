@extends('company/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Dashboard')
@section('main-padding', 'p-0')

@section('content-area')
<div class="flex-1 min-h-0 overflow-auto p-4 md:p-6 space-y-6">

    {{-- ── Bienvenida ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-lg font-bold text-slate-800">
                Bienvenido, {{ auth()->guard('company')->user()?->name ?? 'Usuario' }}
            </h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            </p>
        </div>
        <a href=""
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-cash-register text-xs"></i>
            Nueva Venta
        </a>
    </div>

    {{-- ── Tarjetas de estadísticas ────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Sedes --}}
        <a href="{{ route('company.branches.index') }}"
           class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sedes</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-building text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $totalBranches }}</p>
            <p class="text-xs text-slate-400 mt-1.5">Activas</p>
        </a>

        {{-- Empleados --}}
        <a href="{{ route('company.employees.index') }}"
           class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-violet-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Empleados</p>
                <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-user-tie text-violet-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $totalEmployees }}</p>
            <p class="text-xs text-slate-400 mt-1.5">Registrados</p>
        </a>

        {{-- Productos --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-pills text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $totalProducts }}</p>
            <p class="text-xs text-slate-400 mt-1.5">En inventario</p>
        </div>

        {{-- Stock bajo --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Bajo</p>
                <div class="w-9 h-9 {{ $lowStock > 0 ? 'bg-amber-50' : 'bg-slate-50' }} rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-triangle-exclamation {{ $lowStock > 0 ? 'text-amber-500' : 'text-slate-300' }} text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $lowStock > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $lowStock }}</p>
            <p class="text-xs text-slate-400 mt-1.5">Requieren restock</p>
        </div>

        {{-- Ventas hoy --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Hoy</p>
                <div class="w-9 h-9 bg-sky-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-receipt text-sky-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($todaySales, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1.5">
                <span class="font-semibold text-sky-600">{{ $todayOrdersCount }}</span>
                {{ $todayOrdersCount === 1 ? 'transacción' : 'transacciones' }} · {{ now()->format('d/m/Y') }}
            </p>
        </div>

        {{-- Ventas del mes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas del Mes</p>
                <div class="w-9 h-9 bg-rose-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-chart-line text-rose-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($monthSales, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1.5">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
        </div>

    </div>

    {{-- ── Empleados por sede ──────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-800">Empleados por Sede</h2>
            <a href="{{ route('company.branches.index') }}"
               class="text-xs text-emerald-600 hover:text-emerald-700 font-medium transition-colors">
                Ver sedes →
            </a>
        </div>
        @if($employeesPerBranch->isEmpty())
        <div class="py-8 text-center">
            <p class="text-sm text-slate-400">Sin sedes registradas</p>
        </div>
        @else
        <div class="divide-y divide-slate-100">
            @foreach($employeesPerBranch as $branch)
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-building text-emerald-600 text-xs"></i>
                    </div>
                    <span class="text-sm text-slate-700 font-medium">{{ $branch->name }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-800">{{ $branch->employees_count }}</span>
                    <span class="text-xs text-slate-400">{{ $branch->employees_count === 1 ? 'empleado' : 'empleados' }}</span>
                    @php
                        $pct = $totalEmployees > 0 ? round(($branch->employees_count / $totalEmployees) * 100) : 0;
                    @endphp
                    <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden ml-2">
                        <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs text-slate-400 w-8 text-right">{{ $pct }}%</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Ventas recientes ─────────────────────────────────────── --}}
    <div>
        {{-- Ventas recientes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-800">Ventas Recientes</h2>
                <span class="text-xs text-slate-400">Últimas {{ $recentOrders->count() }}</span>
            </div>

            @if($recentOrders->isEmpty())
            <div class="py-14 text-center">
                <i class="fas fa-receipt text-4xl text-slate-100 mb-3 block"></i>
                <p class="text-sm text-slate-400 font-medium">Sin ventas aún</p>
                <p class="text-xs text-slate-300 mt-1">Las ventas registradas aparecerán aquí</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">#</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Cliente</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Total</th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $pagos = [1 => 'Efectivo', 2 => 'Tarjeta', 3 => 'Transferencia', 4 => 'Yape'];
                            $comprobantes = [1 => 'Boleta', 2 => 'Factura', 3 => 'Nota de Venta'];
                        @endphp
                        @foreach($recentOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3 text-xs text-slate-400 font-mono">#{{ $order->id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-700 text-xs">
                                    {{ $order->customer_name ?: 'Cliente general' }}
                                </p>
                                @if($order->customer_document)
                                <p class="text-slate-400 text-xs font-mono">{{ $order->customer_document }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                    {{ $pagos[$order->payment_type] ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800 text-xs">
                                S/ {{ number_format($order->total, 2) }}
                            </td>
   