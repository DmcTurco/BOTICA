@extends('employee/layouts/base')

@section('title', 'Traspaso #' . $transfer->id)
@section('main-padding', 'p-2 md:p-3')

@php
    $emp    = auth()->guard('employee')->user();
    $canVoid = $transfer->isActive() && $emp->hasPrivilege(\App\Models\Employee::PRIV_ELIMINAR_GUIA_SAL);
    $fmt    = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full">

    <div class="flex items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.transfers.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Traspaso #{{ $transfer->id }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @if($canVoid)
        <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','flex','important')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-red-50 border border-red-200 text-red-600 text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-ban text-xs"></i> Anular
        </button>
        @endif
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div><p class="text-xs text-slate-400 mb-1">Origen</p><p class="text-sm font-medium text-slate-800">{{ $transfer->fromBranch->name }}</p></div>
            <div><p class="text-xs text-slate-400 mb-1">Destino</p><p class="text-sm font-medium text-slate-800">{{ $transfer->toBranch->name }}</p></div>
            <div><p class="text-xs text-slate-400 mb-1">Registrado por</p><p class="text-sm font-medium text-slate-800">{{ $transfer->employee?->name ?? '—' }}</p></div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Estado</p>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $transfer->isActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">{{ $transfer->isActive() ? 'Vigente' : 'Anulado' }}</span>
            </div>
        </div>
        @if($transfer->notes)
        <div class="mt-4 p-3 bg-slate-50 rounded-lg"><p class="text-xs text-slate-400 mb-1">Observaciones</p><p class="text-sm text-slate-600">{{ $transfer->notes }}</p></div>
        @endif
        @unless($transfer->isActive())
        <div class="mt-4 p-3 bg-red-50 rounded-lg">
            <p class="text-xs text-red-500 mb-1">Anulado el {{ $transfer->voided_at?->format('d/m/Y H:i') }}</p>
            <p class="text-sm text-red-700">{{ $transfer->void_reason }}</p>
        </div>
        @endunless
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Productos traspasados</p></div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($transfer->items as $item)
                <tr>
                    <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $item->product?->name ?? $item->product_code }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $item->product_code }}</p></td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($item->quantity) }} {{ $item->product?->unit?->abbreviation }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($canVoid)
<div id="modalAnular" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form action="{{ route('employee.transfers.void', $transfer) }}" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-ban text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Anular traspaso</h3>
        </div>
        <p class="text-sm text-slate-600">La mercadería vuelve a la sede de origen. Solo es posible si la sede de destino aún tiene esas unidades.</p>
        <input type="text" name="void_reason" required maxlength="255" placeholder="Motivo de la anulación"
               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Anular traspaso</button>
        </div>
    </form>
</div>
@endif
@endsection
