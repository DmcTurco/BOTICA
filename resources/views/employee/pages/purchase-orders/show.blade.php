@extends('employee/layouts/base')

@section('title', 'Orden ' . $order->number)
@section('main-padding', 'p-2 md:p-3')

@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0'; @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full">

    <div class="flex items-center justify-between gap-3 shrink-0 flex-wrap">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.purchase-orders.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors"><i class="fas fa-arrow-left text-sm"></i></a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Orden {{ $order->number }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $order->created_at->format('d/m/Y H:i') }} · <span class="font-medium">{{ $order->status_label }}</span></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg"><i class="fas fa-print text-xs"></i> Imprimir</button>
            @if($order->status === 'pending')
            <a href="{{ route('employee.purchases.create', ['order' => $order->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm"><i class="fas fa-truck-ramp-box text-xs"></i> Recibir mercadería</a>
            <form action="{{ route('employee.purchase-orders.cancel', $order) }}" method="POST" class="inline">@csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-red-50 border border-red-200 text-red-600 text-sm font-medium rounded-lg"><i class="fas fa-ban text-xs"></i> Anular</button>
            </form>
            @endif
        </div>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div><p class="text-xs text-slate-400 mb-1">Proveedor</p><p class="text-sm font-medium text-slate-800">{{ $order->supplier?->name }}</p><p class="text-[10px] text-slate-400">{{ $order->supplier?->ruc }}</p></div>
            <div><p class="text-xs text-slate-400 mb-1">Entregar en</p><p class="text-sm font-medium text-slate-800">{{ $order->branch?->name }}</p></div>
            <div><p class="text-xs text-slate-400 mb-1">Entrega esperada</p><p class="text-sm font-medium text-slate-800">{{ $order->expected_date?->format('d/m/Y') ?? '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-1">Emitida por</p><p class="text-sm font-medium text-slate-800">{{ $order->employee?->name }}</p></div>
        </div>
        @if($order->notes)<div class="mt-4 p-3 bg-slate-50 rounded-lg"><p class="text-xs text-slate-400 mb-1">Observaciones</p><p class="text-sm text-slate-600">{{ $order->notes }}</p></div>@endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Costo unit.</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($order->items as $i)
                <tr>
                    <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $i->product?->name ?? $i->product_code }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $i->product_code }}</p></td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($i->quantity) }} {{ $i->product?->unit?->abbreviation }}</td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-600">S/ {{ number_format($i->unit_cost, 2) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-semibold text-slate-800">S/ {{ number_format($i->quantity * $i->unit_cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot><tr class="bg-slate-50 border-t border-slate-200"><td colspan="3" class="px-5 py-3 text-right text-xs font-semibold text-slate-600">Total estimado</td><td class="px-5 py-3 text-right text-sm font-bold text-slate-800">S/ {{ number_format($order->total, 2) }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endsection
