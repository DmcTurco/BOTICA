@extends('employee/layouts/base')

@section('title', 'Inventario Inicial')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => number_format((float) $n, 2, '.', '');
    $cell  = 'w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500';
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 shrink-0 flex-wrap">
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-slate-800">Inventario Inicial</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Cuenta lo que ya tienes en el estante. La cantidad <strong>fija</strong> el stock de tu sede; solo se guardan las filas que llenes.
                Puedes hacerlo por partes (estante por estante) o con Excel.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('employee.initial-inventory.template', request()->query()) }}" data-libre
               class="inline-flex items-center gap-2 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-medium rounded-lg shadow-sm">
                <i class="fas fa-file-excel text-emerald-600"></i> Descargar plantilla
            </a>
            <button type="button" id="btnImportar"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-medium rounded-lg shadow-sm">
                <i class="fas fa-file-arrow-up text-sky-600"></i> Importar Excel
            </button>
        </div>
    </div>

    @include('employee.partials.alerts')

    {{-- Filtros: para ir por partes (una categoría o un laboratorio a la vez) --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.initial-inventory.index') }}" method="GET" class="flex flex-col lg:flex-row gap-3 lg:items-center">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, código, código de barras o principio activo..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <select data-search name="categoria" class="lg:w-52 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('categoria') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <select data-search name="laboratorio" class="lg:w-52 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los laboratorios</option>
                @foreach($laboratories as $l)<option value="{{ $l->id }}" {{ request('laboratorio') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
            </select>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 whitespace-nowrap">
                <input type="checkbox" name="sin_stock" value="1" class="w-4 h-4 rounded border-slate-300 text-sky-600" {{ request()->boolean('sin_stock') ? 'checked' : '' }}>
                Solo sin stock
            </label>
            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg flex items-center justify-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    {{-- Conteo: la tabla ocupa el alto disponible y el botón de guardar queda fijo abajo --}}
    <form action="{{ route('employee.initial-inventory.store', request()->query()) }}" method="POST" id="formConteo"
          class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Stock</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-sky-700 uppercase tracking-wider w-28">Contado</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Costo S/</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Venta S/</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24 hidden md:table-cell">Lote</th>
                    <th class="text-center px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36 hidden md:table-cell">Vencimiento</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                    @php $stock = (float) ($stocks[$p->code] ?? 0); $k = $p->code; @endphp
                    <tr class="fila-conteo hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2">
                            <p class="text-xs font-medium text-slate-800">{{ $p->name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $p->code }}@if($p->laboratory) · {{ $p->laboratory->name }}@endif @if($p->unit) · {{ $p->unit->abbreviation }}@endif</p>
                        </td>
                        <td class="px-2 py-2 text-center text-xs font-semibold {{ $stock > 0 ? 'text-slate-700' : 'text-slate-300' }}">{{ rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.') }}</td>
                        <td class="px-2 py-2"><input type="number" name="rows[{{ $k }}][quantity]" min="0" step="0.01" value="{{ old("rows.$k.quantity") }}" placeholder="—" class="campo-cantidad {{ $cell }} text-center font-semibold"></td>
                        <td class="px-2 py-2"><input type="number" name="rows[{{ $k }}][cost]" min="0" step="0.01" value="{{ old("rows.$k.cost", (float) $p->purchase_price > 0 ? $money($p->purchase_price) : '') }}" placeholder="0.00" class="campo-cambio {{ $cell }} text-right"></td>
                        <td class="px-2 py-2"><input type="number" name="rows[{{ $k }}][price]" min="0" step="0.01" value="{{ old("rows.$k.price", (float) $p->unit_sale_price > 0 ? $money($p->unit_sale_price) : '') }}" placeholder="sin precio" class="campo-cambio {{ $cell }} text-right {{ (float) $p->unit_sale_price > 0 ? '' : 'border-amber-300' }}"></td>
                        <td class="px-2 py-2 hidden md:table-cell"><input type="text" name="rows[{{ $k }}][batch]" maxlength="30" value="{{ old("rows.$k.batch") }}" placeholder="Opcional" class="campo-cambio {{ $cell }}"></td>
                        <td class="px-2 py-2 hidden md:table-cell"><input type="date" name="rows[{{ $k }}][expiration]" value="{{ old("rows.$k.expiration") }}" class="campo-cambio {{ $cell }}"></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-slate-400">No hay productos con ese filtro</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="shrink-0 px-4 py-3 border-t border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs">{{ $products->links() }}</div>
            @if($products->count())
            <div class="flex items-center gap-3 ml-auto">
                <span class="text-xs text-slate-500"><strong id="contadorContados" class="text-sky-700">0</strong> contado(s) en esta página</span>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i> Guardar conteo
                </button>
            </div>
            @endif
        </div>
    </form>
</div>

{{-- Importar el Excel llenado --}}
<div id="modalImportar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form action="{{ route('employee.initial-inventory.import') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        @csrf
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
            <div class="w-9 h-9 bg-sky-100 rounded-lg flex items-center justify-center shrink-0"><i class="fas fa-file-arrow-up text-sky-600"></i></div>
            <h3 class="text-base font-bold text-slate-800">Importar inventario desde Excel</h3>
        </div>
        <div class="px-5 py-4 space-y-3 text-sm text-slate-600">
            <ol class="list-decimal pl-5 space-y-1">
                <li>Descarga la plantilla (trae tus productos, con el filtro que tengas puesto).</li>
                <li>Llena la columna <strong>cantidad_contada</strong>. Si quieres, también costo, precio, lote y vencimiento (dd/mm/aaaa).</li>
                <li>Guárdala como Excel (.xlsx) o CSV y súbela aquí. Las filas sin cantidad se ignoran.</li>
            </ol>
            <input type="file" name="file" accept=".xlsx,.csv" class="block w-full text-sm text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium">
        </div>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" data-cerrar-modal class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg">Importar</button>
        </div>
    </form>
</div>

{{-- Aviso al salir con cambios sin guardar (en lugar de la alerta del navegador) --}}
<div id="modalSinGuardar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-triangle-exclamation text-amber-600"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Tienes cambios sin guardar</h3>
        </div>
        <p class="text-sm text-slate-600 mb-5">Si sales de esta página se pierde lo que contaste en ella. Guarda el conteo primero.</p>
        <div class="flex gap-3 justify-end">
            <a id="salirSinGuardar" href="#" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Salir sin guardar</a>
            <button type="button" id="guardarYQuedarse" class="px-4 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg">Guardar conteo</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const mostrar = id => document.getElementById(id).style.setProperty('display', 'flex', 'important');
    const ocultar = id => document.getElementById(id).style.setProperty('display', 'none', 'important');
    let hayCambios = false;

    // Fila con cantidad: se resalta y suma al contador
    function contar() {
        let n = 0;
        document.querySelectorAll('.fila-conteo').forEach(fila => {
            const lleno = fila.querySelector('.campo-cantidad').value !== '';
            fila.classList.toggle('bg-sky-50', lleno);
            if (lleno) n++;
        });
        const el = document.getElementById('contadorContados');
        if (el) el.textContent = n;
    }

    document.getElementById('formConteo').addEventListener('input', () => { hayCambios = true; contar(); });
    document.getElementById('formConteo').addEventListener('submit', () => { hayCambios = false; });
    contar();

    // Cambiar de página o de filtro con cambios sin guardar: avisa con el modal del sistema
    document.querySelectorAll('main a[href]:not([data-libre])').forEach(a => a.addEventListener('click', e => {
        if (!hayCambios) return;
        e.preventDefault();
        document.getElementById('salirSinGuardar').href = a.href;
        mostrar('modalSinGuardar');
    }));
    document.querySelector('form[method="GET"]').addEventListener('submit', e => {
        if (!hayCambios) return;
        e.preventDefault();
        const f = e.target;
        document.getElementById('salirSinGuardar').href = f.action + '?' + new URLSearchParams(new FormData(f)).toString();
        mostrar('modalSinGuardar');
    });
    document.getElementById('guardarYQuedarse').addEventListener('click', () => document.getElementById('formConteo').requestSubmit());

    // Importar
    document.getElementById('btnImportar').addEventListener('click', () => mostrar('modalImportar'));
    document.querySelectorAll('[data-cerrar-modal]').forEach(b => b.addEventListener('click', () => ocultar('modalImportar')));
})();
</script>
@endsection
