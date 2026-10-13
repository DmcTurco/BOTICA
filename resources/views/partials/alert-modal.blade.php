{{-- Modal de alerta global: muestra lo que responde el controlador (errores de validación, errores y éxito) --}}
@php
    $alertType     = 'error';
    $alertMessages = $errors->any() ? $errors->all() : [];
    $alertTitle    = $errors->any() ? 'Revisa los datos ingresados' : null;

    // Un error de sesión (por ejemplo "stock insuficiente") también se muestra en el modal
    if (session('error')) {
        $alertMessages = array_merge([session('error')], $alertMessages);
        $alertTitle    = $alertTitle ?? 'No se pudo completar la acción';
    }

    // Registro o edición exitosa (solo si no hay errores)
    if (!$alertMessages && session('success')) {
        $alertType     = 'success';
        $alertMessages = [session('success')];
        $alertTitle    = '¡Listo!';
    }
    $alertMessages = array_values(array_unique($alertMessages));
@endphp

<div id="alertModal" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
            <div id="alertModalIconBox" class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                <i id="alertModalIcon" class="fas fa-circle-exclamation text-red-500"></i>
            </div>
            <h3 id="alertModalTitle" class="text-base font-bold text-slate-800">Revisa los datos ingresados</h3>
        </div>
        <ul id="alertModalList" class="px-5 py-4 space-y-2 text-sm text-slate-700 max-h-72 overflow-auto"></ul>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button type="button" id="alertModalClose"
                    class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                Entendido
            </button>
        </div>
    </div>
</div>

@php
    $alertPayload = ['type' => $alertType, 'title' => $alertTitle, 'messages' => $alertMessages];
@endphp
<script>
(function () {
    var modal = document.getElementById('alertModal');
    var list  = document.getElementById('alertModalList');
    var title = document.getElementById('alertModalTitle');
    var iconBox = document.getElementById('alertModalIconBox');
    var icon = document.getElementById('alertModalIcon');
    var autoClose = null;

    // Abre el modal con una lista de mensajes. Disponible globalmente:
    // showAlertModal('Título', ['mensaje'], 'error' | 'success')
    window.showAlertModal = function (heading, messages, type) {
        var ok = type === 'success';
        title.textContent = heading || (ok ? '¡Listo!' : 'Revisa los datos ingresados');
        iconBox.className = 'w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ' + (ok ? 'bg-emerald-100' : 'bg-red-100');
        icon.className = 'fas ' + (ok ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-500');

        list.innerHTML = '';
        (messages || []).forEach(function (msg) {
            var li = document.createElement('li');
            li.className = 'flex items-start gap-2';
            var dot = document.createElement('i');
            dot.className = ok ? 'fas fa-check text-emerald-500 text-xs mt-1' : 'fas fa-circle-exclamation text-red-400 text-xs mt-1';
            var span = document.createElement('span');
            span.textContent = msg;
            li.appendChild(dot);
            li.appendChild(span);
            list.appendChild(li);
        });
        modal.style.setProperty('display', 'flex', 'important');

        // El aviso de éxito se cierra solo; los errores esperan a que el usuario los lea
        clearTimeout(autoClose);
        if (ok) autoClose = setTimeout(closeAlertModal, 4000);
    };

    function closeAlertModal() {
        clearTimeout(autoClose);
        modal.style.setProperty('display', 'none', 'important');
    }

    document.getElementById('alertModalClose').addEventListener('click', closeAlertModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeAlertModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAlertModal(); });

    // La validación la hace el servidor (Form Request): se desactiva la validación nativa del navegador
    document.querySelectorAll('form').forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');
    });

    var payload = @json($alertPayload);
    if (payload.messages.length) {
        window.showAlertModal(payload.title, payload.messages, payload.type);
    }
})();
</script>

{{-- Confirmación previa: cualquier <form data-confirm="Mensaje"> pide Cancelar / Aceptar antes de enviarse (anulaciones, etc.) --}}
<div id="confirmModal" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-triangle-exclamation text-amber-600"></i>
            </div>
            <div>
                <h3 id="confirmModalTitle" class="text-base font-bold text-slate-800">¿Confirmar acción?</h3>
                <p id="confirmModalText" class="text-sm text-slate-600 mt-1"></p>
            </div>
        </div>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
            <button type="button" id="confirmModalCancel"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancelar</button>
            <button type="button" id="confirmModalOk"
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Aceptar</button>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.getElementById('confirmModal');
    var pending = null; // formulario a la espera de la respuesta

    function hide() { box.style.setProperty('display', 'none', 'important'); pending = null; }

    // Se intercepta el envío de cualquier formulario con data-confirm
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;
        e.preventDefault();
        pending = form;
        document.getElementById('confirmModalTitle').textContent = form.dataset.confirmTitle || '¿Confirmar acción?';
        document.getElementById('confirmModalText').textContent = form.dataset.confirm;
        document.getElementById('confirmModalOk').textContent = form.dataset.confirmOk || 'Aceptar';
        box.style.setProperty('display', 'flex', 'important');
    }, true);

    document.getElementById('confirmModalOk').addEventListener('click', function () {
        var form = pending;
        hide();
        if (form) { form.dataset.confirmed = '1'; form.submit(); }
    });
    document.getElementById('confirmModalCancel').addEventListener('click', hide);
    box.addEventListener('click', function (e) { if (e.target === box) hide(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && pending) hide(); });
})();
</script>
