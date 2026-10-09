@extends('employee/layouts/base')

@section('title', 'Lote ' . $production->batch)
@section('main-padding', 'p-2 md:p-3')

@php
    $fmt  = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
    $unit = $production->formula?->yieldUnit?->abbreviation;
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.productions.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Lote {{ $production->batch }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $production->formula?->name }} · {{ $production->produced_at->format('d/m/Y') }}</p>
        </div>
    </div>

    @include('employee.partials.alerts')

    @if((int) $production->status === 0)
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        <i class="fas fa-ban mr-1"></i> Lote anulado el {{ $production->voided_at?->format('d/m/Y H:i') }} — {{ $production->void_reason }}
    </div>
    @else
    <div class="flex justify-end gap-2">
        <a href="{{ route('employee.productions.label', $production) }}" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-print text-xs"></i> Imprimir etiqueta
        </a>
        <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','flex','important')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-red-50 border border-red-200 text-red-600 text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-ban text-xs"></i> Anular lote
        </button>
    </div>
    @endif

    {{-- Resultado --}}
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">Resultado</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Cantidad obtenida</p>
                <p class="text-lg font-bold text-emerald-900">{{ $fmt($production->quantity_produced) }} {{ $unit }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Costo total</p>
                <p class="text-lg font-bold text-emerald-900">S/. {{ number_format($production->total_cost, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Costo por unidad</p>
                <p class="text-lg font-bold text-emerald-900">S/. {{ number_format($production->unit_cost, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Vencimiento</p>
                <p class="text-lg font-bold text-emerald-900">{{ $production->expiration_date ? $production->expiration_date->format('d/m/Y') : '—' }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $production->expiration_badge['class'] }}">{{ $production->expiration_badge['label'] }}</span>
            </div>
        </div>
    </div>

    {{-- Datos del lote --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Datos del lote</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-slate-400 mb-1">Fórmula</p>
                @if($production->formula && !$production->formula->trashed())
                    <a href="{{ route('employee.formulas.show', $production->formula) }}" class="text-sm font-medium text-emerald-600 hover:underline">{{ $production->formula->code }}</a>
                @else
                    <p class="text-sm font-medium text-slate-800">{{ $production->formula?->code ?? '—' }}</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Veces la fórmula base</p>
                <p class="text-sm font-medium text-slate-800">× {{ $fmt($production->multiplier) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Elaborado por</p>
                <p class="text-sm font-medium text-slate-800">{{ $production->employee?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Sede</p>
                <p class="text-sm font-medium text-slate-800">{{ $production->branch?->name ?? '—' }}</p>
            </div>
            <div class="col-span-2 sm:col-span-4">
                <p class="text-xs text-slate-400 mb-1">Destino</p>
                <p class="text-sm font-medium text-slate-800">
                    @if($production->product_code)
                        Ingresó al stock de «{{ $production->product?->name }}» para venderse en el POS
                    @else
                        Solo registro de lote (no se vende en el POS)
                    @endif
                </p>
            </div>
        </div>
        @if($production->notes)
        <div class="mt-4 p-3 bg-slate-50 rounded-lg">
            <p class="text-xs text-slate-400 mb-1">Observaciones</p>
            <p class="text-sm text-slate-600">{{ $production->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Insumos consumidos --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200">
            <p class="text-sm font-semibold text-slate-800">Insumos consumidos</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Insumo</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Costo unit.</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($production->ingredients as $ing)
                    <tr>
                        <td class="px-5 py-3">
                            <p class="text-xs font-medium text-slate-800">{{ $ing->product?->name ?? $ing->product_code }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $ing->product_code }}</p>
                        </td>
                        <td class="px-5 py-3 text-center text-xs text-slate-700">{{ $fmt($ing->quantity) }} {{ $ing->product?->unit?->abbreviation }}</td>
                        <td class="px-5 py-3 text-right text-xs text-slate-600">S/. {{ number_format($ing->unit_cost, 2) }}</td>
                        <td class="px-5 py-3 text-right text-xs font-medium text-slate-800">S/. {{ number_format($ing->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-200">
                        <td colspan="3" class="px-5 py-3 text-right text-xs font-semibold text-slate-600">Total</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-slate-800">S/. {{ number_format($production->total_cost, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if((int) $production->status === 1)
<div id="modalAnular" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form action="{{ route('employee.productions.void', $production) }}" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-ban text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Anular lote {{ $production->batch }}</h3>
        </div>
        <p class="text-sm text-slate-600">Los insumos vuelven al stock{{ $production->product_code ? ' y se retira el producto final del stock de venta (solo si aún no se vendió)' : '' }}.</p>
        <input type="text" name="void_reason" required maxlength="255" placeholder="Motivo de la anulación"
               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Anular lote</button>
        </div>
    </form>
</div>
@endif
@endsection
