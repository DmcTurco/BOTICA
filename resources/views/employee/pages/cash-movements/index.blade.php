@extends('employee/layouts/base')

@section('title', 'Gastos e Ingresos de Caja')
@section('main-padding', 'p-2 md:p-3')

@php
    $emp = auth()->guard('employee')->user();
    $canExpense = $emp->hasPrivilege(\App\Models\Employee::PRIV_GASTOS_DIA);
    $canIncome  = $emp->hasPrivilege(\App\Models\Employee::PRIV_OTROS_INGRESOS);
    $defaultType = old('type', $canExpense ? 'expense' : 'income');
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Gastos e Ingresos de Caja</h1>
        <p class="text-sm text-slate-500 mt-0.5">Movimientos de efectivo que no son ventas. Se reflejan en el efectivo esperado al cerrar la caja.</p>
    </div>

    @include('employee.partials.alerts')

    @if($caja)
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Apertura</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->opening_amount) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Ventas en efectivo</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->totalCash()) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Otros ingresos</p><p class="text-lg font-bold text-emerald-700">+ {{ $money($caja->otherIncome()) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Gastos</p><p class="text-lg font-bold text-red-600">− {{ $money($caja->expenses()) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Efectivo en caja</p><p class="text-lg font-bold">{{ $money($caja->expectedCash()) }}</p></div>
    </div>

    <form action="{{ route('employee.cash-movements.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm shrink-0">
        @csrf
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl flex items-center gap-2">
            <i class="fas fa-money-bill-transfer text-emerald-600 text-xs"></i>
            <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Nuevo movimiento</span>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo <span class="text-red-500">*</span></label>
                <select name="type" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    @if($canExpense)<option value="expense" {{ $defaultType === 'expense' ? 'selected' : '' }}>Gasto (sale dinero)</option>@endif
                    @if($canIncome)<option value="income" {{ $defaultType === 'income' ? 'selected' : '' }}>Otro ingreso (entra dinero)</option>@endif
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Concepto <span class="text-red-500">*</span></label>
                <input type="text" name="concept" required maxlength="150" value="{{ old('concept') }}" placeholder="Ej: Pago de flete, sencillo recibido"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Monto (S/) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" required min="0.01" step="0.01" value="{{ old('amount') }}"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-5">
                <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="Nota opcional"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-1 flex justify-end">
                <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-save text-xs"></i> Registrar
                </button>
            </div>
        </div>
    </form>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 flex items-center gap-2 shrink-0">
        <i class="fas fa-lock"></i> No tienes una caja abierta. <a href="{{ route('employee.cash-register.show-open') }}" class="underline font-medium">Abre tu caja</a> para registrar movimientos.
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.cash-movements.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div><label class="block text-xs text-slate-500 mb-1">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="block text-xs text-slate-500 mb-1">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Concepto</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Monto</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Registró</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $m)
                    <tr class="hover:bg-slate-50 {{ $m->status ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800 {{ $m->status ? '' : 'line-through' }}">{{ $m->concept }}</p>@if($m->notes)<p class="text-[10px] text-slate-400">{{ $m->notes }}</p>@endif</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $m->type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">{{ \App\Models\CashMovement::TYPE_LABELS[$m->type] }}</span>
                            @unless($m->status)<span class="text-[10px] text-slate-400">anulado</span>@endunless
                        </td>
                        <td class="px-5 py-3 text-right text-xs font-semibold {{ $m->type === 'income' ? 'text-emerald-700' : 'text-red-600' }}">{{ $m->type === 'income' ? '+' : '−' }} {{ $money($m->amount) }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $m->employee?->name }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($m->status && $myRegister && $m->cash_register_id === $myRegister)
                            <form action="{{ route('employee.cash-movements.void', $m) }}" method="POST" class="inline">@csrf
                                <button type="submit" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Anular">
                                    <i class="fas fa-ban text-xs"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No hay movimientos en el rango seleccionado</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())<div class="px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $movements->links() }}</div>@endif
    </div>
</div>
@endsection
