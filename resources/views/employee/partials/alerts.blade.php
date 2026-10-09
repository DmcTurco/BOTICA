{{-- Alertas estándar: éxito, error y errores de validación --}}
@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 shrink-0">
    <i class="fas fa-circle-check text-emerald-500"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700 flex items-center gap-2 shrink-0">
    <i class="fas fa-circle-exclamation text-red-400"></i> {{ session('error') }}
</div>
@endif
@if($errors->any())
<div class="shrink-0 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm space-y-1">
    @foreach($errors->all() as $error)
        <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
    @endforeach
</div>
@endif
