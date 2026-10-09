@extends('employee/layouts/base')

@section('title', 'Estado de cuenta')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full overflow-auto">

    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.receivables.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors"><i class="fas fa-arrow-left text-sm"></i></a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ $client->name }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $client->code }} · {{ $client->document_number ?: 'sin documento' }} {{ $client->phone ? '· ' . $client->phone : '' }}</p>
        </div>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-3 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Línea de crédito</p><p class="text-lg font-bold text-slate-800">{{ $money($client->credit_limit) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Debe</p><p class="text-lg font-bold text-red-600">{{ $money($debt) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Crédito disponible</p><p class="text-lg font-bold">{{ $money($client->creditAvailable()) }}</p></div>
    </div>

    @if($debt > 0)
    <form action="{{ route('employee.receivables.pay', $client) }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        @csrf
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl text-xs font-semibold text-slate-600 uppercase tracking-wider"><i class="fas fa-hand-holding-dollar text-emerald-600 mr-1"></i> Registrar abono</div>
        <div class="p-5 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
            <div><label class="block text-xs text-slate-500 mb-1">Monto</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $debt }}" value="{{ old('amount', $debt) }}" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="block text-xs text-slate-500 mb-1">Forma de pago</label>
                <select name="method" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">@foreach(\App\Models\CashRegister::PAYMENT_METHODS as $k => $l)<option value="{{ $k }}" {{ old('method') == $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-slate-500 mb-1">Fecha</label><input type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="block text-xs text-slate-500 mb-1">Referencia</label><input type="text" name="reference" maxlength="50" placeholder="N° operación" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Cobrar</button>
        </div>
        <p class="px-5 pb-4 text-xs text-slate-400">El abono se aplica primero a las ventas más antiguas. @unless($hasCaja)<span class="text-amber-600">No tienes caja abierta: solo podrás recibir abonos que no sean en efectivo.</span>@endunless</p>
    </form>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Ventas con saldo</p></div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $o)
                <tr>
                    <td class="px-5 py-2.5 text-xs text-slate-600">{{ $o->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-2.5 text-xs font-mono text-slate-700">{{ $o->voucher_number }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-500">Total {{ $money($o->total) }}</td>
                    <td class="px-5 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $o->credit_due_date?->lt(today()) ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">vence {{ $o->credit_due_date?->format('d/m/Y') }}</span></td>
                    <td class="px-5 py-2.5 text-right text-xs font-bold text-red-600">{{ $money($o->credit_balance) }}</td>
                </tr>
                @empty
                <tr><td class="px-5 py-8 text-center text-sm text-slate-400">Este cliente no tiene deudas pendientes</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Últimos abonos</p></div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @foreach($payments as $p)
                <tr>
                    <td class="px-5 py-2 text-xs text-slate-600">{{ $p->paid_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-2 text-xs font-mono text-slate-500">{{ $p->order?->voucher_number }}</td>
                    <td class="px-5 py-2 text-xs text-slate-600">{{ \App\Models\CashRegister::PAYMENT_METHODS[$p->method] ?? '—' }}@if($p->reference) · {{ $p->reference }}@endif</td>
                    <td class="px-5 py-2 text-xs text-slate-400 hidden sm:table-cell">{{ $p->employee?->name }}</td>
                    <td class="px-5 py-2 text-right text-xs font-semibold text-emerald-700">{{ $money($p->amount) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
