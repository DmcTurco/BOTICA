@extends('admin/layouts/base')

@section('title', 'Revisar archivo subido')

@php
    $hasErrors  = count($importErrors) > 0;
    $money      = fn ($v) => 'S/ ' . number_format((float) $v, 2);
    $withoutStock = count($rows) - $withStock;
    $saleValue  = array_sum(array_map(fn ($r) => (float) $r['stock'] * (float) $r['sale_price'], $rows));
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Encabezado + pasos --}}
    <div class="flex items-center justify-between gap-4 flex-wrap shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.client-imports.create') }}" title="Volver"
               class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-100 transition-colors">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Revisa lo que subiste</h1>
                <p class="text-sm text-slate-500 mt-0.5">Todavía no se guardó nada. Revisa la lista completa; al final eliges la compañía y registras.</p>
            </div>
        </div>
        <ol class="flex items-center gap-2 text-xs font-semibold">
            @foreach([['Modelo', 'done'], ['Archivo subido', 'done'], ['Revisar', 'now'], ['Elegir empresa y registrar', 'todo']] as $i => [$step, $state])
                <li class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px]
                        {{ $state === 'done' ? 'bg-emerald-100 text-emerald-700' : ($state === 'now' ? 'bg-violet-700 text-white ring-4 ring-violet-100' : 'bg-slate-100 text-slate-400') }}">
                        @if($state === 'done') <i class="fas fa-check text-[10px]"></i> @else {{ $i + 1 }} @endif
                    </span>
                    <span class="{{ $state === 'now' ? 'text-violet-700' : ($state === 'done' ? 'text-slate-600' : 'text-slate-400') }}">{{ $step }}</span>
                    @if(!$loop->last) <span class="w-6 h-px bg-slate-200"></span> @endif
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Contenido con scroll; la barra de confirmación queda fija abajo --}}
    <div class="flex-1 min-h-0 overflow-auto flex flex-col gap-3 pr-0.5">

        {{-- Destino, archivo y resumen en una sola franja --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3.5 flex flex-wrap items-center gap-x-8 gap-y-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-violet-50 text-violet-700 flex items-center justify-center shrink-0"><i class="fas fa-file-lines"></i></span>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Archivo subido</p>
                    <p class="text-sm font-bold text-slate-800 truncate max-w-[16rem]" title="{{ $fileName }}">{{ $fileName }}</p>
                    <p class="text-xs text-slate-400">{{ $branch ? $company->name . ' · ' . $branch->name : 'Sin compañía elegida todavía' }}</p>
                </div>
            </div>
            <div class="hidden xl:block w-px h-11 bg-slate-200"></div>
            <dl class="flex flex-wrap gap-x-8 gap-y-2 flex-1">
                @foreach([
                    [$hasErrors ? 'Productos válidos' : 'Productos', number_format(count($rows)), 'text-slate-800'],
                    ['Con stock', number_format($withStock), 'text-emerald-600'],
                    ['Sin stock', number_format($withoutStock), 'text-slate-500'],
                    ['Unidades', number_format($units, 0), 'text-slate-800'],
                    ['Valor a costo', $money($cost), 'text-amber-600'],
                    ['Valor a precio de venta', $money($saleValue), 'text-emerald-600'],
                ] as [$label, $value, $tone])
                    <div>
                        <dt class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider whitespace-nowrap">{{ $label }}</dt>
                        <dd class="text-lg font-bold whitespace-nowrap {{ $tone }}">{{ $value }}</dd>
                    </div>
                @endforeach
                @if($hasErrors)
                    <div>
                        <dt class="text-[11px] text-red-400 font-semibold uppercase tracking-wider whitespace-nowrap">Filas con error</dt>
                        <dd class="text-lg font-bold whitespace-nowrap text-red-600">{{ number_format(count($importErrors)) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Errores: bloquean la importación --}}
        @if($hasErrors)
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 shrink-0">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0"><i class="fas fa-circle-xmark"></i></span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-red-700">
                            {{ number_format(count($importErrors)) }} {{ count($importErrors) === 1 ? 'error impide' : 'errores impiden' }} importar este archivo
                        </p>
                        <p class="text-xs text-red-600 mt-0.5">Corrígelos en el archivo y súbelo otra vez. No se guardó nada. La lista de abajo solo trae las filas que sí están bien.</p>
                    </div>
                </div>
                <ul class="mt-3 space-y-1 text-xs text-red-700 list-disc pl-12 max-h-60 overflow-auto">
                    @foreach(array_slice($importErrors, 0, 100) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                @if(count($importErrors) > 100)
                    <p class="text-xs text-red-600 mt-2 pl-12">… y {{ number_format(count($importErrors) - 100) }} más. Corrige estos y vuelve a subir para ver el resto.</p>
                @endif
            </div>
        @endif

        {{-- Códigos de barras que la compañía elegida ya tiene: bloquean el registro --}}
        @if(count($conflicts))
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 shrink-0">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0"><i class="fas fa-ban"></i></span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-red-700">{{ $company->name }} ya tiene {{ number_format(count($conflicts)) }} de estos códigos de barras</p>
                        <p class="text-xs text-red-600 mt-0.5">No se puede registrar en esa compañía tal cual. Elige otra compañía, o quita esos productos del archivo y súbelo otra vez.</p>
                    </div>
                </div>
                <ul class="mt-3 space-y-1 text-xs text-red-700 list-disc pl-12 max-h-48 overflow-auto">
                    @foreach(array_slice($conflicts, 0, 100) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                @if(count($conflicts) > 100)
                    <p class="text-xs text-red-600 mt-2 pl-12">… y {{ number_format(count($conflicts) - 100) }} más.</p>
                @endif
            </div>
        @endif

        {{-- Avisos: no bloquean --}}
        @if(count($importWarnings) || $skipped || $existing > 0)
            <details class="bg-amber-50 border border-amber-200 rounded-xl shrink-0" {{ count($importWarnings) <= 3 ? 'open' : '' }}>
                <summary class="cursor-pointer select-none px-5 py-3.5 flex items-center gap-3 text-sm font-bold text-amber-800">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><i class="fas fa-triangle-exclamation text-sm"></i></span>
                    Avisos que no impiden importar
                    <span class="text-xs font-normal text-amber-700">(clic para ver el detalle)</span>
                    <span class="text-xs font-semibold bg-amber-100 text-amber-700 rounded-full px-2 py-0.5">{{ number_format(count($importWarnings) + ($existing > 0 ? 1 : 0) + ($skipped ? 1 : 0)) }}</span>
                </summary>
                <ul class="px-5 pb-4 pl-16 space-y-1 text-xs text-amber-800 list-disc max-h-60 overflow-auto">
                    @if($existing > 0)
                        <li><b>{{ $company->name }} ya tiene {{ number_format($existing) }} productos.</b> Los del archivo se agregan como productos nuevos.</li>
                    @endif
                    @if($skipped)
                        <li>Se ignoró {{ $skipped }} fila de ejemplo del modelo.</li>
                    @endif
                    @foreach(array_slice($importWarnings, 0, 50) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                    @if(count($importWarnings) > 50)
                        <li>… y {{ number_format(count($importWarnings) - 50) }} avisos más.</li>
                    @endif
                </ul>
            </details>
        @endif

        {{-- Lista completa de lo que se va a registrar --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1 min-h-[26rem] flex flex-col">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-3 flex-wrap shrink-0">
                <div class="relative flex-1 min-w-[16rem]">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="search" id="q" placeholder="Buscar producto, código, laboratorio…" autocomplete="off"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div id="chips" class="flex items-center gap-1.5 flex-wrap text-xs font-semibold"></div>
            </div>

            <div class="flex-1 min-h-0 overflow-auto">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 shadow-[0_1px_0_0_rgb(226,232,240)]">
                        <tr class="text-left uppercase tracking-wider text-[11px]">
                            <th class="px-2.5 py-2.5 font-semibold w-12">Fila</th>
                            <th class="px-2.5 py-2.5 font-semibold whitespace-nowrap">Cód. barras</th>
                            <th class="px-2.5 py-2.5 font-semibold">Producto</th>
                            <th class="px-2.5 py-2.5 font-semibold">Categoría · Laboratorio</th>
                            <th class="px-2.5 py-2.5 font-semibold text-right">Stock</th>
                            <th class="px-2.5 py-2.5 font-semibold text-right">Compra</th>
                            <th class="px-2.5 py-2.5 font-semibold text-right">Venta</th>
                            <th class="px-2.5 py-2.5 font-semibold text-right">Margen</th>
                            <th class="px-2.5 py-2.5 font-semibold">Vence</th>
                            <th class="px-2.5 py-2.5 font-semibold">Presentaciones</th>
                        </tr>
                    </thead>
                    <tbody id="rows" class="divide-y divide-slate-100 text-slate-600"></tbody>
                </table>
                <div id="empty" class="hidden py-14 text-center text-sm text-slate-400">
                    <i class="fas fa-magnifying-glass text-2xl text-slate-300 mb-2 block"></i>
                    Ningún producto coincide con la búsqueda.
                </div>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 shrink-0 flex items-center justify-between gap-3 flex-wrap text-xs text-slate-500">
                <span id="range"></span>
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-1.5">Por página
                        <select id="size" class="border border-slate-300 rounded-md px-1.5 py-1 text-xs bg-white">
                            <option>25</option><option selected>50</option><option>100</option><option>200</option>
                        </select>
                    </label>
                    <button type="button" id="prev" class="px-3 py-1.5 border border-slate-300 rounded-md bg-white hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white font-semibold">Anterior</button>
                    <span id="page" class="font-semibold text-slate-700 min-w-[5.5rem] text-center"></span>
                    <button type="button" id="next" class="px-3 py-1.5 border border-slate-300 rounded-md bg-white hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white font-semibold">Siguiente</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de confirmación: siempre visible. Aquí se elige a qué compañía y sede se registra --}}
    <div class="shrink-0 bg-white rounded-xl border border-slate-200 shadow-[0_-4px_16px_-8px_rgba(15,23,42,0.18)] px-5 py-3.5">
        @if(!$hasErrors)
            <form method="POST" action="{{ route('admin.client-imports.store') }}" id="confirm"
                  class="flex items-center justify-between gap-x-4 gap-y-2 flex-wrap">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="flex items-center gap-x-6 gap-y-2 flex-wrap flex-1 min-w-[20rem]">
                    <div class="shrink-0">
                        <p class="text-sm font-bold text-slate-800"><i class="fas fa-circle-check text-emerald-500 mr-1"></i>{{ number_format(count($rows)) }} productos listos</p>
                        <p class="text-xs text-slate-500">{{ number_format($withStock) }} con stock · {{ $money($cost) }} a costo</p>
                    </div>

                    <div class="flex-1 min-w-[18rem]">
                        <label for="branch_id" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">¿En qué compañía y sede se registran?</label>
                        <select name="branch_id" id="branch_id" data-url="{{ route('admin.client-imports.show', $token) }}"
                                class="w-full max-w-xl px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">Elige la compañía y sede…</option>
                            @foreach($companies as $c)
                                <optgroup label="{{ $c->name }} · {{ number_format($c->products_count) }} productos">
                                    @foreach($c->branches as $br)
                                        <option value="{{ $br->id }}" @selected($branch && $branch->id === $br->id)>{{ $c->name }} — {{ $br->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('admin.client-imports.create') }}"
                       class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" id="go" @disabled(!$branch || count($conflicts) > 0 || $existing > 0)
                            class="px-6 py-2.5 bg-violet-700 hover:bg-violet-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                        Registrar {{ number_format(count($rows)) }} productos
                    </button>
                </div>

                @if(!$branch)
                    <p class="basis-full text-xs text-slate-400 -mt-1">Elige el destino para poder registrar. Si la compañía todavía no existe, créala primero en
                        <a href="{{ route('admin.companies.create') }}" class="text-violet-700 font-semibold hover:underline">Empresas</a>
                        (sin cargar catálogo) y vuelve a subir este archivo.</p>
                @elseif(count($conflicts))
                    <p class="basis-full text-xs text-red-600 font-semibold -mt-1"><i class="fas fa-ban mr-1"></i>Esa compañía ya tiene códigos de barras de este archivo (ver arriba).</p>
                @elseif($existing > 0)
                    <label class="basis-full flex items-start gap-2 -mt-1 cursor-pointer">
                        <input type="checkbox" name="accept_existing" value="1" id="accept"
                               class="mt-0.5 w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-xs text-slate-600">
                            {{ $company->name }} ya tiene {{ number_format($existing) }} productos: entiendo que estos se agregan como nuevos
                            (registrar el mismo archivo dos veces repite los que no tienen código de barras).
                        </span>
                    </label>
                @endif
            </form>
        @else
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm font-bold text-red-700"><i class="fas fa-circle-xmark mr-1"></i> Corrige los {{ number_format(count($importErrors)) }} errores del archivo para poder registrar</p>
                <a href="{{ route('admin.client-imports.create') }}"
                   class="px-6 py-2.5 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Corregir y subir otra vez
                </a>
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    const ROWS = {{ \Illuminate\Support\Js::from($rows) }};
    const today = new Date().toISOString().slice(0, 10);
    const soon = new Date(Date.now() + 90 * 864e5).toISOString().slice(0, 10);
    const fmt = (n) => Number(n).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const num = (n) => Number(n).toLocaleString('es-PE', { maximumFractionDigits: 2 });
    const dmy = (d) => d.split('-').reverse().join('/');

    const FILTERS = [
        ['all',      'Todos',          () => true],
        ['stock',    'Con stock',      (r) => +r.stock > 0],
        ['nostock',  'Sin stock',      (r) => +r.stock <= 0],
        ['noprice',  'Sin precio de venta', (r) => +r.sale_price <= 0],
        ['expiry',   'Con vencimiento', (r) => !!r.expiration],
        ['pack',     'Con caja o blíster', (r) => !!r.pack_qty || !!r.blister_qty],
    ];

    // Texto para buscar, ya sin tildes ni mayúsculas
    const plain = (s) => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    ROWS.forEach((r) => { r._s = plain([r.barcode, r.name, r.category, r.laboratory, r.active_ingredient].join(' ')); });

    const $ = (id) => document.getElementById(id);
    let filter = 'all', query = '', page = 1, size = 50;

    function el(tag, cls, text) {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined) e.textContent = text;
        return e;
    }
    function td(cls, child) {
        const c = el('td', 'px-2.5 py-2 ' + (cls || ''));
        if (child !== undefined) c.append(child);
        return c;
    }
    function pill(text, cls) { return el('span', 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ' + cls, text); }

    function visible() {
        const test = FILTERS.find((f) => f[0] === filter)[2];
        const words = plain(query).split(/\s+/).filter(Boolean);
        return ROWS.filter((r) => test(r) && words.every((w) => r._s.includes(w)));
    }

    function renderChips() {
        const box = $('chips');
        box.replaceChildren();
        FILTERS.forEach(([key, label, fn]) => {
            const count = ROWS.filter(fn).length;
            if (key === 'noprice' && !count) return;
            const b = el('button', 'px-3 py-1.5 rounded-full border transition-colors ' + (key === filter
                ? 'bg-violet-700 border-violet-700 text-white'
                : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50'));
            b.type = 'button';
            b.append(label + ' ');
            b.append(el('span', key === filter ? 'opacity-80' : 'text-slate-400', count.toLocaleString('es-PE')));
            b.addEventListener('click', () => { filter = key; page = 1; render(); });
            box.append(b);
        });
    }

    function render() {
        renderChips();
        const list = visible();
        const pages = Math.max(1, Math.ceil(list.length / size));
        page = Math.min(page, pages);
        const from = (page - 1) * size;
        const slice = list.slice(from, from + size);

        const body = $('rows');
        body.replaceChildren();
        slice.forEach((r) => {
            const tr = el('tr', 'hover:bg-violet-50/40');
            tr.append(td('text-slate-400 tabular-nums', document.createTextNode(r.line)));
            tr.append(td('font-mono whitespace-nowrap', r.barcode
                ? document.createTextNode(r.barcode)
                : el('span', 'text-slate-400 font-sans italic', 'se genera uno')));

            const name = td('min-w-[13rem]');
            name.append(el('div', 'text-slate-800 font-semibold', r.name));
            if (r.active_ingredient) name.append(el('div', 'text-[11px] text-slate-400', r.active_ingredient));
            if (+r.requires_recipe) name.append(pill('Con receta', 'bg-rose-50 text-rose-700 mt-1'));
            tr.append(name);

            const group = td('min-w-[8rem]');
            group.append(el('div', 'text-slate-700', r.category));
            group.append(r.laboratory ? el('div', 'text-[11px] text-slate-400', r.laboratory) : el('div', 'text-[11px] text-slate-300', 'sin laboratorio'));
            tr.append(group);

            tr.append(td('text-right', pill(num(r.stock), +r.stock > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400')));
            tr.append(td('text-right tabular-nums', document.createTextNode(fmt(r.purchase_price))));
            tr.append(td('text-right tabular-nums font-semibold text-slate-800', +r.sale_price > 0
                ? document.createTextNode(fmt(r.sale_price))
                : pill('sin precio', 'bg-amber-50 text-amber-700')));

            const cost = +r.purchase_price, sale = +r.sale_price;
            const margin = cost > 0 && sale > 0 ? ((sale - cost) / cost) * 100 : null;
            tr.append(td('text-right tabular-nums ' + (margin === null ? 'text-slate-300' : margin < 0 ? 'text-red-600 font-semibold' : 'text-slate-500'),
                document.createTextNode(margin === null ? '—' : margin.toFixed(0) + '%')));

            const exp = td('whitespace-nowrap');
            if (r.expiration) {
                const tone = r.expiration < today ? 'bg-red-50 text-red-700' : r.expiration <= soon ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600';
                exp.append(pill((r.expiration < today ? 'Vencido · ' : '') + dmy(r.expiration), tone));
            } else {
                exp.append(el('span', 'text-slate-300', '—'));
            }
            tr.append(exp);

            const pres = td('min-w-[8.5rem]');
            const chips = el('div', 'flex flex-wrap gap-1');
            if (r.blister_qty) chips.append(pill('Blíster ×' + num(r.blister_qty) + ' · ' + fmt(r.blister_price), 'bg-sky-50 text-sky-700'));
            if (r.pack_qty) chips.append(pill('Caja ×' + num(r.pack_qty) + ' · ' + fmt(r.pack_price), 'bg-violet-50 text-violet-700'));
            if (!r.blister_qty && !r.pack_qty) chips.append(el('span', 'text-slate-300', '—'));
            pres.append(chips);
            tr.append(pres);

            body.append(tr);
        });

        $('empty').classList.toggle('hidden', list.length > 0);
        $('range').textContent = list.length
            ? 'Mostrando ' + (from + 1).toLocaleString('es-PE') + '–' + (from + slice.length).toLocaleString('es-PE') + ' de ' + list.length.toLocaleString('es-PE') + (list.length === ROWS.length ? ' productos' : ' (filtrados de ' + ROWS.length.toLocaleString('es-PE') + ')')
            : 'Sin resultados';
        $('page').textContent = 'Página ' + page + ' de ' + pages;
        $('prev').disabled = page <= 1;
        $('next').disabled = page >= pages;
    }

    let timer;
    $('q').addEventListener('input', (e) => { clearTimeout(timer); timer = setTimeout(() => { query = e.target.value; page = 1; render(); }, 150); });
    $('size').addEventListener('change', (e) => { size = +e.target.value; page = 1; render(); });
    $('prev').addEventListener('click', () => { page--; render(); });
    $('next').addEventListener('click', () => { page++; render(); });
    render();

    // Al elegir la compañía y sede se recarga esta misma vista con ella, para ver si ya tiene esos códigos o productos
    const select = $('branch_id');
    if (select) select.addEventListener('change', () => { window.location = select.dataset.url + (select.value ? '?branch_id=' + select.value : ''); });

    // Con productos ya existentes hay que marcar la casilla; y un solo clic (evita registrar dos veces)
    const form = $('confirm'), go = $('go'), accept = $('accept');
    if (accept) accept.addEventListener('change', () => { go.disabled = !accept.checked; });
    if (form) form.addEventListener('submit', () => { go.disabled = true; go.textContent = 'Registrando…'; });
})();
</script>
@endsection
