@extends('employee/layouts/base')

@section('title', $formula->name)
@section('main-padding', 'p-2 md:p-3')

@php
    $emp = auth()->guard('employee')->user();
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
    $unit = $formula->yieldUnit?->abbreviation;
    $costo = $formula->estimatedCost();
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-5xl mx-auto w-full">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.formulas.index') }}"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">{{ $formula->name }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    <span class="font-mono">{{ $formula->code }}</span>
                    @if($formula->pharmaceutical_form) · {{ $formula->pharmaceutical_form }} @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($emp->hasPrivilege(\App\Models\Employee::PRIV_PRODUCIR_FORMULAS) && $formula->status)
            <a href="{{ route('employee.productions.create', ['formula' => $formula->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-flask-vial text-xs"></i> Preparar lote
            </a>
            @endif
            <a href="{{ route('employee.formulas.edit', $formula) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-pen text-xs"></i> Editar
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 shrink-0">
        <i class="fas fa-circle-check text-emerald-500"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Resultado final --}}
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">Resultado final de la fórmula base</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Se obtiene</p>
                <p class="text-lg font-bold text-emerald-900">{{ $fmt($formula->yield_quantity) }} {{ $unit }}</p>
                <p class="text-xs text-emerald-800">{{ $formula->name }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Costo estimado</p>
                <p class="text-lg font-bold text-emerald-900">S/. {{ number_format($costo, 2) }}</p>
                <p class="text-xs text-emerald-800">S/. {{ number_format($formula->yield_quantity > 0 ? $costo / $formula->yield_quantity : 0, 2) }} por {{ $unit ?: 'unidad' }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Vida útil</p>
                <p class="text-lg font-bold text-emerald-900">{{ $formula->shelf_life_days ? $formula->shelf_life_days . ' días' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-emerald-700/70 mb-1">Venta en POS</p>
                @if($formula->sell_in_pos)
                    <p class="text-lg font-bold text-emerald-900">Sí</p>
                    <p class="text-xs text-emerald-800">{{ $formula->product?->name ?? 'Producto no disponible' }}</p>
                @else
                    <p class="text-lg font-bold text-slate-500">No</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">

        {{-- Insumos --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200">
                <p class="text-sm font-semibold text-slate-800">Insumos <span class="text-xs font-normal text-slate-400">por {{ $fmt($formula->yield_quantity) }} {{ $unit }}</span></p>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Insumo</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($formula->ingredients as $ing)
                    <tr>
                        <td class="px-5 py-2.5">
                            <p class="text-xs font-medium text-slate-800">{{ $ing->product?->name ?? $ing->product_code }}</p>
                            @if($ing->notes)<p class="text-[10px] text-slate-400">{{ $ing->notes }}</p>@endif
                        </td>
                        <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($ing->quantity) }} {{ $ing->product?->unit?->abbreviation }}</td>
                        <td class="px-5 py-2.5 text-right text-xs text-slate-600">S/. {{ number_format($ing->quantity * ($ing->product?->purchase_price ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Preparación y datos --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Modo de preparación</p>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ $formula->procedure ?: 'Sin instrucciones registradas.' }}</p>
                @if($formula->storage)
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-1">Conservación</p>
                <p class="text-sm text-slate-700">{{ $formula->storage }}</p>
                @endif
                @if($formula->description)
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-1">Indicación</p>
                <p class="text-sm text-slate-700">{{ $formula->description }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Últimos lotes --}}
    @if($emp->hasPrivilege(\App\Models\Employee::PRIV_PRODUCIR_FORMULAS))
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200">
            <p class="text-sm font-semibold text-slate-800">Últimos lotes preparados</p>
        </div>
        @if($lotes->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">Aún no se ha preparado esta fórmula.</p>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Lote</th>
                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Elaboración</th>
                    <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                    <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vence</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($lotes as $lote)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5"><a href="{{ route('employee.productions.show', $lote) }}" class="font-mono text-xs text-emerald-600 hover:underline">{{ $lote->batch }}</a></td>
                    <td class="px-5 py-2.5 text-xs text-slate-600">{{ $lote->produced_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($lote->quantity_produced) }} {{ $unit }}</td>
                    <td class="px-5 py-2.5 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $lote->expiration_badge['class'] }}">
                            {{ $lote->expiration_date ? $lote->expiration_date->format('d/m/Y') : $lote->expiration_badge['label'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif
</div>
@endsection
