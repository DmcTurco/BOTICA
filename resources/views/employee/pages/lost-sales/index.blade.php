@extends('employee/layouts/base')

@section('title', 'Ventas Perdidas')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header: el registro se hace en su propio formulario --}}
    <div class="flex items-center justify-between gap-3 shrink-0 flex-wrap">
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-slate-800">Ventas Perdidas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Lo que el cliente pidió y no pudiste vender, para saber qué reponer o incorporar</p>
        </div>
        <form action="{{ route('employee.lost-sales.index') }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
            <input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
        <a href="{{ route('employee.lost-sales.create') }}"
           class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Registrar venta perdida
        </a>
    </div>

    @include('employee.partials.alerts')

    {{-- Ranking y registro: ambos ocupan el alto disponible y hacen scroll por dentro --}}
    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <p class="shrink-0 px-5 py-3 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">Lo que más se perdió</p>
            <div class="flex-1 min-h-0 overflow-auto px-5 py-2">
                @forelse($ranking as $i => $r)
                <div class="flex items-center justify-between py-1.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <p class="text-sm text-slate-700"><span class="text-slate-400 text-xs mr-1">{{ $i + 1 }}.</span>{{ $r->product_name }}</p>
                    <span class="text-xs font-semibold text-red-600">{{ $r->times }}×</span>
                </div>
                @empty
                <p class="text-sm text-slate-400 py-2">Sin registros en el rango</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <p class="shrink-0 px-5 py-3 border-b border-slate-200 text-sm font-semibold text-slate-800">Registro</p>
            <div class="flex-1 min-h-0 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cant.</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Motivo</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Registró</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lostSales as $s)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-2.5 text-xs text-slate-500">{{ $s->created_at->format('d/m H:i') }}</td>
                            <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $s->product_name }}</p>@if($s->notes)<p class="text-[10px] text-slate-400">{{ $s->notes }}</p>@endif</td>
                            <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ rtrim(rtrim(number_format($s->quantity, 2), '0'), '.') }}</td>
                            <td class="px-5 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ $s->reason_label }}</span></td>
                            <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $s->employee?->name }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No hay ventas perdidas en el rango</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($lostSales->hasPages())<div class="shrink-0 px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $lostSales->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
