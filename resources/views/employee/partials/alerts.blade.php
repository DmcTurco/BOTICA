{{-- Alertas estándar: éxito. Los errores (validación y session 'error') salen en el modal global partials/alert-modal --}}
@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 shrink-0">
    <i class="fas fa-circle-check text-emerald-500"></i> {{ session('success') }}
</div>
@endif
