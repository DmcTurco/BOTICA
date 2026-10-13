{{-- Modal de alerta global: muestra los errores de validación y los mensajes de error que vienen del controlador --}}
@php
    $alertMessages = $errors->any() ? $errors->all() : [];
    $alertTitle    = $errors->any() ? 'Revisa los datos ingresados' : null;

    // Un error de sesión (por ejemplo "stock insuficiente") también se muestra en el modal
    if (session('error')) {
        $alertMessages = array_merge([session('error')], $alertMessages);
        $alertTitle    = $alertTitle ?? 'No se pudo completar la acción';
    }
    $alertMessages = array_values(array_unique($alertMessages));
@endphp

<div id="alertModal" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
            <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas fa-circle-exclamation text-red-500"></i>
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
    $alertPayload = ['title' => $alertTitle, 'messages' => $alertMessages];
@endphp
<script>
(function () {
    var modal = document.getElementById('alertModal');
    var list  = document.getElementById('alertModalList');
    var title = document.getElementById('alertModalTitle');

    // Abre el modal con una lista de mensajes. Disponible globalmente: showAlertModal('Título', ['mensaje'])
    window.showAlertModal = function (heading, messages) {
        title.textContent = heading || 'Revisa los datos ingresados';
        list.innerHTML = '';
        (messages || []).forEach(function (msg) {
            var li = document.createElement('li');
            li.className = 'flex items-start gap-2';
            var icon = document.createElement('i');
            icon.className = 'fas fa-circle-exclamation text-red-400 text-xs mt-1';
            var span = document.createElement('span');
            span.textContent = msg;
            li.appendChild(icon);
            li.appendChild(span);
            list.appendChild(li);
        });
        modal.style.setProperty('display', 'flex', 'important');
    };

    function closeAlertModal() {
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
        window.showAlertModal(payload.title, payload.messages);
    }
})();
</script>
