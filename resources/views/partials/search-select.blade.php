{{-- Buscador desplegable: convierte cualquier <select data-search> en un campo con búsqueda.
     El <select> original sigue existiendo (oculto) y conserva el valor, así que el formulario y el servidor no cambian.
     Para contenido dinámico: window.initSearchSelects(contenedor) --}}
<style>
    .ss-list { position: fixed; z-index: 60; background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem;
               box-shadow: 0 10px 25px -5px rgba(0,0,0,.15); max-height: 14rem; overflow: auto; display: none; }
    .ss-item { padding: .5rem .75rem; font-size: .875rem; color: #334155; cursor: pointer; }
    .ss-item.ss-active, .ss-item:hover { background: #ecfdf5; }
    .ss-item.ss-selected { font-weight: 600; color: #047857; }
    .ss-empty-option { color: #94a3b8; border-bottom: 1px solid #f1f5f9; }
    .ss-none { padding: .5rem .75rem; font-size: .8rem; color: #94a3b8; }
</style>
<script>
(function () {
    // Clases de tamaño/posición del select que pasan al contenedor; el resto de estilos van al campo de texto
    var LAYOUT = /^(sm:|md:|lg:|xl:)?(w-|min-w-|max-w-|flex-|shrink|grow|col-span|basis-)/;

    // Sin tildes ni mayúsculas, para que "analgesicos" encuentre "Analgésicos"
    function norm(s) { return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }

    var list = document.createElement('div');
    list.className = 'ss-list';
    document.body.appendChild(list);
    var current = null; // combo abierto: { select, input, options, active }

    function place(input) {
        var r = input.getBoundingClientRect();
        list.style.left = r.left + 'px';
        list.style.top = (r.bottom + 4) + 'px';
        list.style.width = r.width + 'px';
        // Si no cabe abajo, se abre hacia arriba
        if (r.bottom + 230 > window.innerHeight && r.top > 230) {
            list.style.top = (r.top - Math.min(list.offsetHeight, 224) - 4) + 'px';
        }
    }

    function labelOf(select) {
        var o = select.options[select.selectedIndex];
        return o && o.value !== '' ? o.text.trim() : '';
    }

    function render(combo) {
        var q = norm(combo.input.value);
        // Si el texto es el de la opción elegida se muestran todas, no solo esa
        var showAll = q === '' || norm(labelOf(combo.select)) === q;
        var items = [];
        Array.prototype.forEach.call(combo.select.options, function (o) {
            if (o.value === '') {
                if (!combo.select.required) items.push({ value: '', text: o.text.trim(), empty: true });
            } else if (showAll || norm(o.text).indexOf(q) !== -1) {
                items.push({ value: o.value, text: o.text.trim() });
            }
        });
        combo.items = items;
        combo.active = Math.max(0, items.findIndex(function (i) { return i.value === combo.select.value && i.value !== ''; }));
        if (!showAll) combo.active = items.findIndex(function (i) { return !i.empty; });
        if (combo.active < 0) combo.active = 0;

        list.innerHTML = '';
        if (!items.length || (items.length === 1 && items[0].empty)) {
            var none = document.createElement('div');
            none.className = 'ss-none';
            none.textContent = 'Sin resultados';
            list.appendChild(none);
        }
        items.slice(0, 80).forEach(function (it, idx) {
            var d = document.createElement('div');
            d.className = 'ss-item' + (it.empty ? ' ss-empty-option' : '') + (it.value === combo.select.value && it.value !== '' ? ' ss-selected' : '');
            d.textContent = it.text;
            d.dataset.idx = idx;
            d.addEventListener('mousedown', function (e) { e.preventDefault(); choose(combo, it); });
            list.appendChild(d);
        });
        highlight(combo);
        list.style.display = 'block';
        place(combo.input);
    }

    function highlight(combo) {
        Array.prototype.forEach.call(list.querySelectorAll('.ss-item'), function (el) {
            el.classList.toggle('ss-active', Number(el.dataset.idx) === combo.active);
            if (Number(el.dataset.idx) === combo.active) el.scrollIntoView({ block: 'nearest' });
        });
    }

    function choose(combo, item) {
        var changed = combo.select.value !== item.value;
        combo.select.value = item.value;
        combo.input.value = item.value === '' ? '' : item.text;
        close(combo);
        if (changed) combo.select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function close(combo) {
        list.style.display = 'none';
        if (current === combo) current = null;
    }

    function build(select) {
        if (select.dataset.ssReady) return;
        select.dataset.ssReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'relative';
        var input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');

        // Reparto de clases: tamaño al contenedor, aspecto al campo
        var inputClasses = ['w-full', 'pr-8'];
        select.className.split(/\s+/).forEach(function (c) {
            if (!c || c === 'select2' || c === 'hidden') return;
            if (LAYOUT.test(c)) wrap.classList.add(c);
            else if (!/^(pr-|pl-)/.test(c)) inputClasses.push(c);
        });
        input.className = inputClasses.join(' ');
        var empty = select.options[0] && select.options[0].value === '' ? select.options[0].text.trim() : '';
        input.placeholder = empty || 'Buscar...';
        if (select.id) input.id = select.id + '_search';
        input.value = labelOf(select);

        var chevron = document.createElement('i');
        chevron.className = 'fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none';

        select.style.display = 'none';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(input);
        wrap.appendChild(chevron);
        wrap.appendChild(select);

        // Que un <label for="id"> lleve el foco al campo visible
        var label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        if (label) label.setAttribute('for', input.id);

        var combo = { select: select, input: input, items: [], active: 0 };

        input.addEventListener('focus', function () { current = combo; input.select(); render(combo); });
        input.addEventListener('click', function () { if (list.style.display !== 'block') { current = combo; render(combo); } });
        input.addEventListener('input', function () { current = combo; render(combo); });
        input.addEventListener('keydown', function (e) {
            if (list.style.display !== 'block') {
                if (e.key === 'ArrowDown') { current = combo; render(combo); e.preventDefault(); }
                return;
            }
            if (e.key === 'ArrowDown') { combo.active = Math.min(combo.active + 1, combo.items.length - 1); highlight(combo); e.preventDefault(); }
            else if (e.key === 'ArrowUp') { combo.active = Math.max(combo.active - 1, 0); highlight(combo); e.preventDefault(); }
            else if (e.key === 'Enter') { if (combo.items[combo.active]) choose(combo, combo.items[combo.active]); e.preventDefault(); }
            else if (e.key === 'Escape') { input.value = labelOf(select); close(combo); e.stopPropagation(); }
        });
        input.addEventListener('blur', function () {
            // Lo escrito que no coincide con ninguna opción se descarta; vacío limpia la selección (si no es obligatoria)
            if (input.value.trim() === '' && !select.required && select.value !== '') {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            input.value = labelOf(select);
            close(combo);
        });

        // Cambios hechos por código sobre el select (select.value = ...; dispatchEvent('change'))
        select.addEventListener('change', function () { input.value = labelOf(select); });
        select.searchRefresh = function () { input.value = labelOf(select); };
    }

    window.initSearchSelects = function (root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('select[data-search]'), build);
    };

    // El listado flotante sigue al campo al hacer scroll o redimensionar
    window.addEventListener('scroll', function () { if (current) place(current.input); }, true);
    window.addEventListener('resize', function () { if (current) place(current.input); });

    // Se enciende al final de la carga, cuando el formulario y sus scripts ya existen
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { window.initSearchSelects(); });
    else window.initSearchSelects();
})();
</script>
