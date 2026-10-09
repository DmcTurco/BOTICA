@extends('employee/layouts/base')

@section('title', 'Preparaciones')
@section('main-padding', 'p-2 md:p-3')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Preparaciones</h1>
            <p class="text-sm text-slate-500 mt-0.5">Lotes elaborados en el laboratorio, con su vencimiento</p>
        </div>
        <a href="{{ route('employee.productions.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Preparar Lote
        </a>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 shrink-0">
        <i class="fas fa-circle-check text-emerald-500"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.productions.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="N° de lote o nombre de la fórmula...">
                </div>
                <select name="vencimiento"
                        class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todos los vencimientos</option>
                    <option value="por_vencer" {{ request('vencimiento') === 'por_vencer' ? 'selected' : '' }}>Por vencer (30 días)</option>
                    <option value="vencido" {{ request('vencimiento') === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar','vencimiento']))
                <a href="{{ route('employee.productions.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Historial de lotes
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $producciones->total() }} registros</span>
            </p>
        </div>

        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Lote</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fórmula</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Elaboración</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vencimiento</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Costo</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($producciones as $p)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $p->batch }}@if((int) $p->status === 0) <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-50 text-red-600 font-sans">Anulado</span>@endif</td>
                        <td class="px-5 py-3">
                            <p class="text-xs font-medium text-slate-800">{{ $p->formula?->name ?? '—' }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $p->formula?->code }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600 hidden md:table-cell">{{ $p->produced_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-700">{{ $fmt($p->quantity_produced) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $p->expiration_badge['class'] }}">
                                {{ $p->expiration_date ? $p->expiration_date->format('d/m/Y') : $p->expiration_badge['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-xs text-slate-700 hidden lg:table-cell">S/. {{ number_format($p->total_cost, 2) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('employee.productions.show', $p) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="Ver detalle">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-flask-vial text-3xl"></i>
                                <p class="text-sm">No hay preparaciones registradas</p>
                                <a href="{{ route('employee.productions.create') }}" class="text-emerald-600 text-sm hover:underline">Preparar primer lote</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($producciones->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $producciones->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
