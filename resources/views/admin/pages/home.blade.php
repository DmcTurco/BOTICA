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
            <p class="text-2xl font-bold text-slate-800">{{ $stats['companies'] }}</p>
            <p class="text-xs text-slate-500 mt-1.5">Registradas en el sistema</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Empleados</p>
                <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-indigo-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $stats['employees'] }}</p>
            <p class="text-xs text-slate-500 mt-1.5">En todas las empresas</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mensualidades</p>
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($stats['subscriptionIncome'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1.5">Cobrado este mes</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Mes</p>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-bar text-amber-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($stats['salesMonth'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1.5">Total del mes actual</p>
        </div>
    </div>

    {{-- Empresas con la mensualidad vencida: en gracia (aviso rojo) o ya bloqueadas --}}
    @if($overdue->isNotEmpty())
    <div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2">
            <i class="fas fa-circle-exclamation text-red-500"></i>
            <h2 class="text-sm font-semibold text-slate-800">Mensualidades vencidas ({{ $overdue->count() }})</h2>
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach($overdue as $company)
            <li class="px-5 py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800">{{ $company->name }}</p>
                    <p class="text-xs text-slate-500">
                        Venció el {{ $company->paid_until->format('d/m/Y') }} ·
                        {{ $company->subscriptionStatus() === \App\Models\Company::SUB_SUSPENDED ? 'Bloqueada' : 'Se bloquea el ' . $company->subscriptionBlocksOn()->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.subscriptions.show', $company) }}"
                   class="px-3 py-1.5 bg-violet-700 hover:bg-violet-800 text-white text-xs font-semibold rounded-lg transition-colors shrink-0">
                    Registrar pago
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Acciones rápidas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('admin.companies.index') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 transition-colors group">
                <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                    <i class="fas fa-building text-violet-700"></i>
                </div>
                <span class="text-xs font-medium text-slate-600 group-hover:text-violet-700 text-center">Gestionar Empresas</span>
            </a>
            <a href="{{ route('admin.companies.create') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors group">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                    <i class="fas fa-plus text-indigo-600"></i>
                </div>
                <span class="text-xs font-medium text-slate-600 group-hover:text-indigo-600 text-center">Nueva Empresa</span>
            </a>
        </div>
    </div>

</div>
@endsection
