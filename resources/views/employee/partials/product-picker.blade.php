{{--
    Buscador de productos + tabla de líneas con cantidad. Se usa dentro de un <form>.
    Variables:
      $pickerProducts : [['code','name','unit','stock'], ...]
      $pickerName     : prefijo de los campos (por defecto "items") → items[0][product_code], items[0][quantity]
      $pickerInitial  : líneas iniciales [['product_code' => , 'quantity' => ]]
      $pickerLimit    : true = la cantidad no puede superar el stock del producto
--}}
@php
    $pickerName    = $pickerName ?? 'items';
    $pickerInitial = array_values($pickerInitial ?? []);
    $pickerLimit   = $pickerLimit ?? false;
@endphp

<div class="space-y-3">
    <div class="relative max-w-xl">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" id="pickerSearch" autocomplete="off" placeholder="Busca un producto para agregarlo..."
               class="w-full pl-8 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
        <div id="pickerResults" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-auto"></div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-center px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Stock</th>
                    <th class="text-center px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">Cantidad</th>
                    <th class="w-10"></th>
                </tr>
            </thead>
            <tbody id="pickerRows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="pickerEmpty" class="text-center text-sm text-slate-400 py-6">Aún no hay productos. Búscalos arriba para agregarlos.</p>
    </div>
</div>

<script>
(function () {
    const productos = @json($pickerProducts);
    const prefijo = @json($pickerName);
    const limitar = @json($pickerLimit);
    const filas = document.getElementById('pickerRows');
    const buscador = document.getElementById('pickerSearch');
    const resultados = document.getElementById('pickerResults');
    let idx = 0;

    const esc = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };

    function agregar(code, cantidad) {
        if (filas.querySelector(`tr[data-code="${CSS.escape(code)}"]`)) return;
        const p = productos.find(x => x.code === code);
        if (!p) return;
        const i = idx++;
        const tr = document.createElement('tr');
        tr.dataset.code = code;
        tr.innerHTML = `
            <td class="px-3 py-2">
                <input type="hidden" name="${prefijo}[${i}][product_code]" value="${esc(code)}">
                <p class="font-medium text-slate-800 text-xs">${esc(p.name)}</p>
                <p class="text-[10px] text-slate-400 font-mono">${esc(code)}</p>
            </td>
            <td class="px-3 py-2 text-center text-xs text-slate-600">${p.stock ?? '—'}</td>
            <td class="px-3 py-2">
                <div class="flex items-center gap-1">
                    <input type="number" name="${prefijo}[${i}][quantity]" value="${cantidad ?? ''}" min="0.01" step="0.01" required
                           ${limitar && p.stock != null ? `max="${p.stock}"` : ''}
                           class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="text-xs text-slate-400 w-8">${esc(p.unit || '')}</span>
                </div>
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="picker-quitar w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 mx-auto">
                    <i class="fas fa-trash text-[10px] pointer-events-none"></i>
                </button>
            </td>`;
        filas.appendChild(tr);
        actualizar();
    }

    function actualizar() {
        document.getElementById('pickerEmpty').classList.toggle('hidden', filas.children.length > 0);
    }

    filas.addEventListener('click', e => {
        const btn = e.target.closest('.picker-quitar');
        if (btn) { btn.closest('tr').remove(); actualizar(); }
    });

    buscador.addEventListener('input', () => {
        const q = buscador.value.toLowerCase().trim();
        if (!q) { resultados.classList.add('hidden'); return; }
        const hallados = productos.filter(p => (p.name + ' ' + p.code).toLowerCase().includes(q)).slice(0, 30);
        resultados.innerHTML = hallados.length ? hallados.map(p => `
            <button type="button" data-code="${esc(p.code)}" class="picker-item w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-slate-100 last:border-0">
                <p class="text-xs font-medium text-slate-800">${esc(p.name)}</p>
                <p class="text-[10px] text-slate-400 font-mono">${esc(p.code)}${p.stock != null ? ' · stock ' + p.stock : ''}</p>
            </button>`).join('') : '<p class="px-3 py-3 text-xs text-slate-400">Sin resultados</p>';
        resultados.classList.remove('hidden');
    });
    buscador.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
    resultados.addEventListener('click', e => {
        const item = e.target.closest('.picker-item');
        if (!item) return;
        agregar(item.dataset.code, '');
        buscador.value = '';
        resultados.classList.add('hidden');
        filas.lastElementChild?.querySelector('input[type=number]')?.focus();
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('#pickerSearch') && !e.target.closest('#pickerResults')) resultados.classList.add('hidden');
    });

    @json($pickerInitial).forEach(l => agregar(l.product_code, l.quantity));
    actualizar();
})();
</script>
