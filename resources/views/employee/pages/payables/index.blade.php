@extends('employee/layouts/base')

@section('title', 'Cuentas por Pagar')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Cuentas por Pagar</h1>
        <p class="text-sm text-slate-500 mt-0.5">Compras a crédito con saldo pendiente con tus proveedores</p>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Deuda total</p><p class="text-lg font-bold text-slate-800">{{ $money($totalDebt) }}</p></div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4"><p class="text-xs text-red-600">Vencida</p><p class="text-lg font-bold text-red-700">{{ $money($overdue) }}</p></div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.payables.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <select name="proveedor" class="sm:w-64 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los proveedores</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" {{ request('proveedor') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
            </select>
            <select name="estado" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas</option>
                <option value="vencidas" {{ request('estado') === 'vencidas' ? 'selected' : '' }}>Solo vencidas</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    @if($bySupplier->count() > 1)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Por proveedor</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1.5">
            @foreach($bySupplier as $name => $row)
            <div class="flex justify-between text-sm"><span class="text-slate-600">{{ $name }} <span class="text-slate-400 text-xs">({{ $row['count'] }})</span></span><span class="font-semibold text-slate-800">{{ $money($row['debt']) }}</span></div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Compra</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Proveedor</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vence</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Total</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo</th>
                <th class="px-5 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $p)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800">{{ $p->document_number ?: '#' . $p->id }}</p><p class="text-[10px] text-slate-400">{{ $p->purchased_at->format('d/m/Y') }}</p></td>
                    <td class="px-5 py-3 text-xs text-slate-600 hidden sm:table-cell">{{ $p->supplierRecord?->name ?? $p->supplier ?: '—' }}</td>
                    <td class="px-5 py-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $p->isOverdue() ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">{{ $p->due_date?->format('d/m/Y') ?? '—' }}</span></td>
                    <td class="px-5 py-3 text-right text-xs text-slate-600 hidden md:table-cell">{{ $money($p->total) }}</td>
                    <td class="px-5 py-3 text-right text-xs font-bold text-red-600">{{ $money($p->balance) }}</td>
                    <td class="px-5 py-3 text-right"><a href="{{ route('employee.purchases.show', $p) }}" class="px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg">Pagar</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400"><i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i><p>No tienes deudas pendientes con proveedores</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
