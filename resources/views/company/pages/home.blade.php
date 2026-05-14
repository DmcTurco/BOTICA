@extends('company/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Dashboard')
@section('main-padding', 'p-0')

@section('content-area')
<div class="flex-1 overflow-auto p-4 md:p-6 space-y-6">

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
        <a href="{{ route('company.orders.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-cash-register text-xs"></i>
            Nueva Venta
        </a>
    </div>

    {{-- ── Tarjetas de estadísticas ────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

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
            <p class="text-xs text-slate-400 mt-1.5">{{ now()->format('d/m/Y') }}</p>
        </div>

        {{-- Ventas del mes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas del Mes</p>
                <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-chart-line text-violet-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($monthSales, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1.5">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
        </div>

    </div>

    {{-- ── Acceso rápido + Ventas recientes ────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Acceso rápido --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Acceso Rápido</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-3">

                <a href="{{ route('company.orders.index') }}"
                   class="flex flex-col items-center gap-2 p-3.5 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 transition-colors group">
                    <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                        <i class="fas fa-cash-register text-emerald-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-emerald-700 text-center leading-tight">Nueva Venta</span>
                </a>

                <a href="{{ route('company.products.create') }}"
                   class="flex flex-col items-center gap-2 p-3.5 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50 transition-colors group">
                    <div class="w-9 h-9 bg-sky-100 rounded-lg flex items-center justify-center group-hover:bg-sky-200 transition-colors">
                        <i class="fas fa-box-open text-sky-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-sky-700 text-center leading-tight">Nuevo Producto</span>
                </a>

                <a href="{{ route('company.products.index') }}"
                   class="flex flex-col items-center gap-2 p-3.5 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50 transition-colors group">
                    <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                        <i class="fas fa-pills text-amber-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-amber-700 text-center leading-tight">Ver Productos</span>
                </a>

                <a href="{{ route('company.categories.index') }}"
                   class="flex flex-col items-center gap-2 p-3.5 rounded-xl border border-slate-200 hover:border-rose-300 hover:bg-rose-50 transition-colors group">
                    <div class="w-9 h-9 bg-rose-100 rounded-lg flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                        <i class="fas fa-tags text-rose-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-rose-700 text-center leading-tight">Categorías</span>
                </a>

                <a href="{{ route('company.laboratories.index') }}"
                   class="flex flex-col items-center gap-2 p-3.5 rounded-xl border border-slate-200 hover:border-cyan-300 hover:bg-cyan-50 transition-colors group">
                    <div class="w-9 h-9 bg-cyan-100 rounded-lg flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                        <i class="fas fa-flask text-cyan-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-cyan-700 text-center leading-tight">Laboratorios</span>
                </a>

            </div>
        </div>

        {{-- Ventas recientes --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
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
                            <td class="px-5 py-3 text-right text-xs text-slate-400 whitespace-nowrap">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>

</div>
{{-- ── Modal apertura de caja ──────────────────────────────── --}}
<div id="modalCaja"
     class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
     style="{{ request('open_cash') || (session('_flash.error') && str_contains(session('_flash.error',''), 'caja')) ? '' : 'display:none!important' }}">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                <i class="fas fa-cash-register text-emerald-600"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">Apertura de Caja</h3>
                <p class="text-xs text-slate-400 mt-0.5">Ingresa el efectivo inicial para comenzar</p>
            </div>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('company.cash-register.open') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Monto inicial (S/)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-sm">S/</span>
                    <input type="number" name="opening_amount" step="0.01" min="0"
                           class="w-full pl-9 pr-4 py-3 text-lg font-semibold border border-slate-300 rounded-xl
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="0.00" autofocus>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">
                    Cuenta el efectivo en caja antes de comenzar a vender.
                </p>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl
                           transition-colors flex items-center justify-center gap-2 shadow-sm">
                <i class="fas fa-lock-open text-sm"></i>
                Abrir Caja y Comenzar
            </button>
        </form>

    </div>
</div>

@push('scripts')
<script>
    // Si viene el parámetro open_cash en la URL, mostrar el modal
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open_cash')) {
            document.getElementById('modalCaja').style.removeProperty('display');
        }
    });
</script>
@endpush

@endsection
