@extends('employee/layouts/base')

@section('title', 'Cuentas por Cobrar')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Cuentas por Cobrar</h1>
        <p class="text-sm text-slate-500 mt-0.5">Clientes que compraron a crédito (fiado) y todavía deben</p>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Por cobrar</p><p class="text-lg font-bold text-slate-800">{{ $money($totalDebt) }}</p></div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4"><p class="text-xs text-red-600">Vencido</p><p class="text-lg font-bold text-red-700">{{ $money($overdue) }}</p></div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.receivables.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o documento del cliente..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select name="estado" class="sm:w-44 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos</option>
                <option value="vencidos" {{ request('estado') === 'vencidos' ? 'selected' : '' }}>Con deuda vencida</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cliente</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Ventas</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Vence</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vencido</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Debe</th>
                <th class="px-5 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($clients as $row)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800">{{ $row->client?->name }}</p><p class="text-[10px] text-slate-400">{{ $row->client?->document_number }} {{ $row->client?->phone ? '· ' . $row->client->phone : '' }}</p></td>
                    <td class="px-5 py-3 text-center text-xs text-slate-600 hidden sm:table-cell">{{ $row->orders }}</td>
                    <td class="px-5 py-3 text-center text-xs text-slate-600 hidden md:table-cell">{{ $row->oldest?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-5 py-3 text-right text-xs font-semibold {{ $row->overdue > 0 ? 'text-red-600' : 'text-slate-300' }}">{{ $row->overdue > 0 ? $money($row->overdue) : '—' }}</td>
                    <td class="px-5 py-3 text-right text-xs font-bold text-slate-800">{{ $money($row->debt) }}</td>
                    <td class="px-5 py-3 text-right"><a href="{{ route('employee.receivables.show', $row->client) }}" class="px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg">Cobrar</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400"><i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i><p>Nadie te debe por ventas a crédito</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
