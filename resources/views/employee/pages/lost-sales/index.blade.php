@extends('employee/layouts/base')

@section('title', 'Ventas Perdidas')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Ventas Perdidas</h1>
        <p class="text-sm text-slate-500 mt-0.5">Anota lo que el cliente pidió y no pudiste vender, para saber qué reponer o incorporar</p>
    </div>

    @include('employee.partials.alerts')

    <form action="{{ route('employee.lost-sales.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm shrink-0">
        @csrf
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl flex items-center gap-2">
            <i class="fas fa-circle-minus text-emerald-600 text-xs"></i>
            <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Registrar venta perdida</span>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-4">
            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Producto pedido <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" id="productName" required maxlength="150" list="catalogo" value="{{ old('product_name') }}" placeholder="Nombre del producto (del catálogo o libre)"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <input type="hidden" name="product_code" id="productCode" value="{{ old('product_code') }}">
                <datalist id="catalogo">@foreach($products as $p)<option value="{{ $p['name'] }}">@endforeach</datalist>
            </div>
            <div class="sm:col-span-1">
                <label class="block text-xs font-medium text-slate-600 mb-1">Cantidad <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" required min="0.01" step="0.01" value="{{ old('quantity', 1) }}"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Motivo <span class="text-red-500">*</span></label>
                <select name="reason" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    @foreach(\App\Models\LostSale::REASONS as $k => $label)<option value="{{ $k }}" {{ old('reason') === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-5">
                <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="Nota opcional (ej. el cliente aceptó una alternativa)"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-1">
                <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center justify-center gap-2"><i class="fas fa-save text-xs"></i> Registrar</button>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Lo que más se perdió</p>
            @forelse($ranking as $i => $r)
            <div class="flex items-center justify-between py-1.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                <p class="text-sm text-slate-700"><span class="text-slate-400 text-xs mr-1">{{ $i + 1 }}.</span>{{ $r->product_name }}</p>
                <span class="text-xs font-semibold text-red-600">{{ $r->times }}×</span>
            </div>
            @empty
            <p class="text-sm text-slate-400">Sin registros en el rango</p>
            @endforelse
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
                <p class="text-sm font-semibold text-slate-800">Registro</p>
                <form action="{{ route('employee.lost-sales.index') }}" method="GET" class="flex items-center gap-2">
                    <input type="date" name="fecha_desde" value="{{ $desde }}" class="px-2 py-1.5 text-xs border border-slate-300 rounded-lg">
                    <input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-2 py-1.5 text-xs border border-slate-300 rounded-lg">
                    <button class="px-3 py-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">Filtrar</button>
                </form>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($lostSales as $s)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5 text-xs text-slate-500">{{ $s->created_at->format('d/m H:i') }}</td>
                        <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $s->product_name }}</p>@if($s->notes)<p class="text-[10px] text-slate-400">{{ $s->notes }}</p>@endif</td>
                        <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ rtrim(rtrim(number_format($s->quantity, 2), '0'), '.') }}</td>
                        <td class="px-5 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ $s->reason_label }}</span></td>
                        <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $s->employee?->name }}</td>
                    </tr>
                    @empty
                    <tr><td class="px-5 py-10 text-center text-sm text-slate-400">No hay ventas perdidas en el rango</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($lostSales->hasPages())<div class="px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $lostSales->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Si el nombre coincide con un producto del catálogo, se guarda también su código
const catalogo = @json($products);
document.getElementById('productName').addEventListener('input', function () {
    const p = catalogo.find(x => x.name.toLowerCase() === this.value.trim().toLowerCase());
    document.getElementById('productCode').value = p ? p.code : '';
});
</script>
@endsection
