@extends('admin/layouts/base')

@section('title', 'Dashboard Admin')

@section('content-area')
<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-slate-800">Panel de Administración</h1>
        <p class="text-sm text-slate-500 mt-0.5">Visión global del sistema BOTICA</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Empresas</p>
                <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-violet-700 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">Registradas en el sistema</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Empleados</p>
                <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-indigo-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">En todas las empresas</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-pills text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">0</p>
            <p class="text-xs text-slate-500 mt-1.5">En inventario global</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Mes</p>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-bar text-amber-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/. 0.00</p>
            <p class="text-xs text-slate-500 mt-1.5">Total del mes actual</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Acciones rápidas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="#"
               class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 transition-colors group">
                <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                    <i class="fas fa-building text-violet-700"></i>
                </div>
                <span class="text-xs font-medium text-slate-600 group-hover:text-violet-700 text-center">Gestionar Empresas</span>
            </a>
            <a href="#"
               class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors group">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                    <i class="fas fa-users text-indigo-600"></i>
                </div>
                <span class="text-xs font-medium text-slate-600 group-hover:text-indigo-600 text-center">Gestionar Usuarios</span>
            </a>
        </div>
    </div>

</div>
@endsection
