@extends('company/layouts/base')

@section('title', 'Dashboard')

@section('content-area')
<div class="space-y-6">

    {{-- Banner de bienvenida --}}
    <div class="rounded-2xl overflow-hidden relative" style="background:linear-gradient(135deg,#15803d 0%,#059669 60%,#0d9488 100%)">
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full" style="background:rgba(255,255,255,.07)"></div>
            <div class="absolute -bottom-16 -left-8 w-56 h-56 rounded-full" style="background:rgba(255,255,255,.05)"></div>
            <div class="absolute top-4 right-32 w-24 h-24 rounded-full" style="background:rgba(255,255,255,.06)"></div>
        </div>
        <div class="relative px-6 py-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-emerald-100 text-sm font-medium mb-1">
                    <i class="fas fa-leaf mr-1.5"></i>Bienvenido de nuevo
                </p>
                <h1 class="text-2xl font-bold text-white">
                    {{ auth()->guard('company')->user()?->name ?? 'Administrador' }}
                </h1>
                <p class="text-emerald-200 text-sm mt-1">Aquí tienes el resumen de tu farmacia hoy</p>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                <a href="{{ route('company.sales.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-semibold rounded-xl transition-colors backdrop-blur-sm">
                    <i class="fas fa-cash-register text-xs"></i> Nueva Venta
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Hoy</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/. 0.00</p>
            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                <span class="text-emerald-500 font-medium flex items-center gap-0.5">
                    <i class="fas fa-arrow-up text-xs"></i>0%
                </span> vs ayer
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos</p>
                <div class="w-9 h-9 bg-sky-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-pills text-sky-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">En inventario</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Bajo</p>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-triangle-exclamation text-amber-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">Requieren restock</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Mes</p>
                <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-violet-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/. 0.00</p>
            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                <span class="text-emerald-500 font-medium flex items-center gap-0.5">
                    <i class="fas fa-arrow-up text-xs"></i>0%
                </span> vs mes anterior
            </p>
        </div>
    </div>

    {{-- Acceso rápido + Estado --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Acceso Rápido</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <a href="{{ route('company.sales.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 transition-colors group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                        <i class="fas fa-cash-register text-emerald-600"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-emerald-700 text-center">Nueva Venta</span>
                </a>
                <a href="{{ route('company.products.create') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50 transition-colors group">
                    <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center group-hover:bg-sky-200 transition-colors">
                        <i class="fas fa-box-open text-sky-600"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-sky-700 text-center">Nuevo Producto</span>
                </a>
                <a href="{{ route('company.products.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50 transition-colors group">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                        <i class="fas fa-pills text-amber-600"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-amber-700 text-center">Ver Productos</span>
                </a>
                <a href="{{ route('company.categories.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-rose-300 hover:bg-rose-50 transition-colors group">
                    <div class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                        <i class="fas fa-tags text-rose-600"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-rose-700 text-center">Categorías</span>
                </a>
                <a href="{{ route('company.laboratories.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-cyan-300 hover:bg-cyan-50 transition-colors group">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                        <i class="fas fa-flask text-cyan-600"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-cyan-700 text-center">Laboratorios</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Estado del Sistema</h2>
            <div class="space-y-1">
                <div class="flex items-center justify-between py-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-circle text-emerald-400 text-[9px]"></i> Base de datos
                    </div>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Online</span>
                </div>
                <div class="flex items-center justify-between py-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-circle text-emerald-400 text-[9px]"></i> Laravel
                    </div>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">v12.x</span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-circle text-emerald-400 text-[9px]"></i> Tailwind CSS
                    </div>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">v4.0</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
