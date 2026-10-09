@extends('employee/layouts/base')

@section('title', 'Nuevo Traspaso')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.transfers.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Nuevo Traspaso</h1>
            <p class="text-sm text-slate-500 mt-0.5">Envía mercadería de tu sede a otra sede de la empresa</p>
        </div>
    </div>

    @include('employee.partials.alerts')

    <form action="{{ route('employee.transfers.store') }}" method="POST" class="flex-1 min-h-0 overflow-auto space-y-3">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl flex items-center gap-2">
                <i class="fas fa-right-left text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Destino</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sede de destino <span class="text-red-500">*</span></label>
                    <select name="to_branch_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">— Selecciona la sede —</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('to_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @if($branches->isEmpty())
                        <p class="text-xs text-amber-600 mt-1">No hay otras sedes activas en tu empresa.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Observaciones</label>
                    <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="Opcional"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl flex items-center gap-2">
                <i class="fas fa-boxes-stacked text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Productos a enviar</span>
            </div>
            <div class="p-5">
                @php
                    $pickerProducts = $products;
                    $pickerLimit    = true;
                    $pickerInitial  = old('items', []);
                @endphp
                @include('employee.partials.product-picker')
            </div>
        </div>

        <div class="flex justify-end gap-3 pb-2">
            <a href="{{ route('employee.transfers.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</a>
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center gap-2">
                <i class="fas fa-paper-plane text-xs"></i> Registrar traspaso
            </button>
        </div>
    </form>
</div>
@endsection
