{{-- Condición de pago de la compra, saldo y pagos al proveedor --}}
@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $canPay = auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_CUENTAS_PAGAR);
@endphp

@if($purchase->payment_condition === 'credit')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Compra a crédito</p>
        @if($purchase->isOverdue())
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Vencida el {{ $purchase->due_date->format('d/m/Y') }}</span>
        @elseif($purchase->balance > 0)
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Vence el {{ $purchase->due_date?->format('d/m/Y') }}</span>
        @else
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Pagada</span>
        @endif
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div><p class="text-xs text-slate-400 mb-1">Proveedor</p><p class="text-sm font-medium text-slate-800">{{ $purchase->supplierRecord?->name ?? $purchase->supplier ?: '—' }}</p></div>
        <div><p class="text-xs text-slate-400 mb-1">Total</p><p class="text-sm font-medium text-slate-800">{{ $money($purchase->total) }}</p></div>
        <div><p class="text-xs text-slate-400 mb-1">Pagado</p><p class="text-sm font-medium text-emerald-700">{{ $money($purchase->paid_amount) }}</p></div>
        <div><p class="text-xs text-slate-400 mb-1">Saldo</p><p class="text-sm font-bold {{ $purchase->balance > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $money($purchase->balance) }}</p></div>
    </div>

    @if($purchase->payments->isNotEmpty())
    <table class="w-full text-sm mt-4">
        <tbody class="divide-y divide-slate-100">
            @foreach($purchase->payments as $p)
            <tr>
                <td class="py-2 text-xs text-slate-500">{{ $p->paid_at->format('d/m/Y') }}</td>
                <td class="py-2 text-xs text-slate-600">{{ \App\Models\CashRegister::PAYMENT_TYPE_LABELS[$p->method] ?? '—' }}@if($p->reference) · {{ $p->reference }}@endif</td>
                <td class="py-2 text-xs text-slate-500 hidden sm:table-cell">{{ $p->employee?->name }}</td>
                <td class="py-2 text-right text-xs font-semibold text-emerald-700">{{ $money($p->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($canPay && $purchase->balance > 0)
    <form action="{{ route('employee.payables.pay', $purchase) }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
        @csrf
        <div><label class="block text-xs text-slate-500 mb-1">Monto</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $purchase->balance }}" value="{{ $purchase->balance }}" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="block text-xs text-slate-500 mb-1">Forma de pago</label>
            <select name="method" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">@foreach(\App\Models\CashRegister::PAYMENT_METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="block text-xs text-slate-500 mb-1">Fecha</label><input type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="block text-xs text-slate-500 mb-1">Referencia</label><input type="text" name="reference" maxlength="50" placeholder="N° operación" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Registrar pago</button>
    </form>
    @endif
</div>
@elseif($purchase->supplierRecord || $purchase->supplier)
<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3 text-sm text-slate-600">
    Proveedor: <strong class="text-slate-800">{{ $purchase->supplierRecord?->name ?? $purchase->supplier }}</strong> · Pagada al contado
</div>
@endif
