@extends('employee/layouts/base')

@section('title', 'Actualización de Precios')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => number_format($n, 2, '.', ''); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Actualización de Precios</h1>
        <p class="text-sm text-slate-500 mt-0.5">Edita el precio de compra y de venta (por unidad, sin IGV) de varios productos a la vez. Cada cambio queda registrado.</p>
    </div>

    @include('employee.partials.alerts')

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.prices.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select name="category" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <select name="laboratory" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los laboratorios</option>
                @foreach($laboratories as $l)<option value="{{ $l->id }}" {{ request('laboratory') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-search text-xs"></i> Filtrar</button>
        </form>
    </div>

    {{-- Ajuste por porcentaje a todo lo filtrado --}}
    <form action="{{ route('employee.prices.bulk', request()->query()) }}" method="POST" id="formBulk"
          class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 shrink-0 flex flex-col sm:flex-row sm:items-center gap-3">
        @csrf
        <p class="text-sm text-amber-900 flex-1"><i class="fas fa-percent mr-1"></i> Aplicar un porcentaje a <strong>los {{ $products->total() }} productos</strong> que muestra este filtro</p>
        <div class="flex items-center gap-2 flex-wrap">
            <input type="number" name="percent" step="0.1" min="-90" max="500" placeholder="Ej: 5 o -3" required class="w-28 px-3 py-2 text-sm border border-amber-300 rounded-lg bg-white">
            <select name="target" class="px-3 py-2 text-sm border border-amber-300 rounded-lg bg-white">
                <option value="sale">Precio de venta</option>
                <option value="purchase">Precio de compra</option>
                <option value="both">Ambos</option>
            </select>
            <button type="button" id="btnBulk" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg">Aplicar</button>
        </div>
    </form>

    {{-- Precios por producto --}}
    <form action="{{ route('employee.prices.update') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf @method('PUT')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">P. compra</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">P. venta</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24 hidden sm:table-cell">Margen</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2"><p class="text-xs font-medium text-slate-800">{{ $p->name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $p->code }}@if($p->laboratory) · {{ $p->laboratory->name }}@endif</p></td>
                        <td class="px-5 py-2 text-right"><input type="number" step="0.01" min="0" name="prices[{{ $p->code }}][purchase_price]" value="{{ $money($p->purchase_price) }}" class="precio-compra w-28 px-2 py-1.5 text-xs text-right border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></td>
                        <td class="px-5 py-2 text-right"><input type="number" step="0.01" min="0" name="prices[{{ $p->code }}][unit_sale_price]" value="{{ $money($p->unit_sale_price) }}" class="precio-venta w-28 px-2 py-1.5 text-xs text-right border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></td>
                        <td class="px-5 py-2 text-right text-xs font-medium hidden sm:table-cell margen">—</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No hay productos con ese filtro</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
            <div>{{ $products->links() }}</div>
            @if($products->count())
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center gap-2"><i class="fas fa-save text-xs"></i> Guardar precios de esta página</button>
            @endif
        </div>
    </form>

    {{-- Últimos cambios --}}
    @if($changes->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Últimos cambios de precio</p></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-slate-100">
            @foreach($changes as $c)
            <tr>
                <td class="px-5 py-2 text-xs text-slate-500">{{ $c->created_at->format('d/m H:i') }}</td>
                <td class="px-5 py-2 text-xs text-slate-800">{{ $c->product?->name ?? $c->product_code }}</td>
                <td class="px-5 py-2 text-xs text-slate-500 hidden sm:table-cell">{{ \App\Models\PriceChange::FIELD_LABELS[$c->field] ?? $c->field }}</td>
                <td class="px-5 py-2 text-xs text-slate-700">S/ {{ number_format($c->old_value, 2) }} → <strong>S/ {{ number_format($c->new_value, 2) }}</strong></td>
                <td class="px-5 py-2 text-xs text-slate-500 hidden md:table-cell">{{ $c->employee?->name }}</td>
            </tr>
            @endforeach
        </tbody></table>
    </div>
    @endif
</div>

{{-- Confirmación del ajuste masivo --}}
<div id="modalBulk" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-percent text-amber-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Confirmar ajuste de precios</h3>
        </div>
        <p class="text-sm text-slate-600 mb-6" id="textoBulk"></p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalBulk').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="button" onclick="document.getElementById('formBulk').submit()" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg">Aplicar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Margen de cada fila = (venta − compra) / venta; en rojo si se vende por debajo del costo
function margen(tr) {
    const c = parseFloat(tr.querySelector('.precio-compra').value) || 0;
    const v = parseFloat(tr.querySelector('.precio-venta').value) || 0;
    const cel = tr.querySelector('.margen');
    cel.textContent = v > 0 ? Math.round((v - c) / v * 1000) / 10 + '%' : '—';
    cel.classList.toggle('text-red-600', v < c);
    cel.classList.toggle('text-slate-600', v >= c);
}
document.querySelectorAll('tbody tr').forEach(tr => { if (tr.querySelector('.precio-venta')) margen(tr); });
document.addEventListener('input', e => { if (e.target.matches('.precio-compra, .precio-venta')) margen(e.target.closest('tr')); });

document.getElementById('btnBulk').addEventListener('click', function () {
    const f = document.getElementById('formBulk');
    if (!f.reportValidity()) return;
    const objetivo = f.target.options[f.target.selectedIndex].text.toLowerCase();
    document.getElementById('textoBulk').innerHTML =
        `Se aplicará <strong>${f.percent.value}%</strong> al <strong>${objetivo}</strong> de <strong>{{ $products->total() }} productos</strong>. Cada cambio queda registrado.`;
    document.getElementById('modalBulk').style.setProperty('display', 'flex', 'important');
});
</script>
@endsection
