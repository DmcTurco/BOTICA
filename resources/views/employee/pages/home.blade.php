@extends('employee/layouts/base')

@section('title', 'Dashboard')

@section('content-area')
<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-slate-800">Mi Panel</h1>
        <p class="text-sm text-slate-500 mt-0.5">Bienvenido, {{ auth()->guard('employee')->user()?->name ?? 'empleado' }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Hoy</p>
                <div class="w-9 h-9 bg-sky-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cash-register text-sky-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">Atenciones realizadas</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Hoy</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/. 0.00</p>
            <p class="text-xs text-slate-500 mt-1.5">Monto facturado</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Turno</p>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-amber-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">—</p>
            <p class="text-xs text-slate-500 mt-1.5">Sin turno asignado</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Acciones rápidas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <a href="#"
               class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-sky-300 hover:bg-sky-50 transition-colors group">
                <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center group-hover:bg-sky-200 transition-colors">
                    <i class="fas fa-cash-register text-sky-600"></i>
                </div>
                <span class="text-xs font-medium text-slate-600 group-hover:text-sky-600 text-center">Nueva Venta</span>
            </a>
        </div>
    </div>

</div>
@endsection
