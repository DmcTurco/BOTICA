@extends('employee/layouts/base')

@section('title', 'Ajuste de Inventario')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header: el registro se hace en su propio formulario --}}
    <div class="flex items-center justify-between gap-3 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Ajuste de Inventario</h1>
            <p class="text-sm text-slate-500 mt-0.5">Corrige el stock por conteo físico, merma, vencimiento o pérdida. Cada ajuste queda en el Kardex.</p>
        </div>
        <a href="{{ route('employee.adjustments.create') }}"
           class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo ajuste
        </a>
    </div>

    @include('employee.partials.alerts')

    {{-- Historial: ocupa el alto disponible y hace scroll por dentro --}}
    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="shrink-0 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-800">Ajustes de la sede</p>
            <span class="text-xs text-slate-400">{{ $adjustments->total() }} {{ $adjustments->total() === 1 ? 'ajuste' : 'ajustes' }}</span>
        </div>
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ajuste</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($adjustments as $mov)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5 text-xs text-slate-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-2.5 text-xs text-slate-800">{{ $mov->product?->name ?? $mov->product_code }}</td>
                        <td class="px-5 py-2.5 text-center">
                            <span class="font-semibold text-xs {{ $mov->quantity >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}</span>
                        </td>
                        <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $mov->balance }}</td>
                        <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $mov->notes }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">Aún no hay ajustes registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())
        <div class="shrink-0 px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $adjustments->links() }}</div>
        @endif
    </div>
</div>
@endsection
