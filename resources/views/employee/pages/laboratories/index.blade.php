@extends('employee/layouts/base')

@section('title', 'Laboratorios')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Laboratorios</h1>
            <p class="text-sm text-slate-500 mt-0.5">Administra los laboratorios y fabricantes</p>
        </div>
        <a href="{{ route('employee.laboratories.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo Laboratorio
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.laboratories.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Nombre, descripción o país...">
                </div>
                <select name="estado"
                        class="sm:w-44 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-search text-xs"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar','estado']))
                <a href="{{ route('employee.laboratories.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Cabecera del card --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Lista de laboratorios
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $laboratorios->total() }} registros</span>
            </p>
            <div class="relative">
                <button id="exportBtn"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors">
                    <i class="fas fa-download text-xs"></i> Exportar
                </button>
                <div id="exportMenu" class="absolute right-0 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-lg z-10" style="display:none">
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-t-lg">
                        <i class="fas fa-file-excel text-emerald-600 text-xs"></i> Excel
                    </a>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-b-lg">
                        <i class="fas fa-file-pdf text-red-600 text-xs"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Área scrollable --}}
        <div class="flex-1 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Descripción</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">País</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Contacto</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($laboratorios as $laboratorio)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-sky-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas fa-flask text-sky-600 text-xs"></i>
                                </div>
                                <span class="font-medium text-slate-800">{{ $laboratorio->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs hidden md:table-cell max-w-xs truncate">
                            {{ Str::limit($laboratorio->description, 60) ?: '—' }}
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs hidden lg:table-cell">
                            {{ $laboratorio->country ?: '—' }}
                        </td>
                        <td class="px-5 py-3 text-xs hidden lg:table-cell">
                            <div class="space-y-0.5">
                                @if($laboratorio->phone)
                                    <p class="text-slate-500"><i class="fas fa-phone text-[10px] mr-1"></i>{{ $laboratorio->phone }}</p>
                                @endif
                                @if($laboratorio->email)
                                    <p class="text-slate-500"><i class="fas fa-envelope text-[10px] mr-1"></i>{{ $laboratorio->email }}</p>
                                @endif
                                @if(!$laboratorio->phone && !$laboratorio->email)
                                    <span class="text-slate-300">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                                {{ $laboratorio->productos_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($laboratorio->status)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Activo</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('employee.laboratories.edit', $laboratorio->id) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" title="Editar">
                                    <i class="fas fa-pencil text-xs"></i>
                                </a>
                                <button type="button"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors btn-eliminar"
                                        data-id="{{ $laboratorio->id }}" data-nombre="{{ $laboratorio->name }}" title="Eliminar">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-flask text-3xl"></i>
                                <p class="text-sm">No hay laboratorios registrados</p>
                                <a href="{{ route('employee.laboratories.create') }}" class="text-emerald-600 text-sm hover:underline">Crear primer laboratorio</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginador (footer estático) --}}
        @if($laboratorios->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $laboratorios->withQueryString()->links() }}
        </div>
        @endif

    </div>
</div>

{{-- Modal eliminar --}}
<div id="modalEliminar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-trash text-red-600 text-sm"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Confirmar eliminación</h3>
        </div>
        <p class="text-slate-600 text-sm mb-1">
            ¿Seguro que deseas eliminar el laboratorio <strong id="nombre-eliminar" class="text-slate-800"></strong>?
        </p>
        <p class="text-red-600 text-xs mb-6">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="cerrarModal()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancelar
            </button>
            <form id="form-eliminar" action="" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function cerrarModal() {
    document.getElementById('modalEliminar').style.setProperty('display', 'none', 'important');
}
$(document).ready(function() {
    $('#exportBtn').on('click', function(e) {
        e.stopPropagation();
        const m = document.getElementById('exportMenu');
        m.style.display = m.style.display === 'none' ? 'block' : 'none';
    });
    $(document).on('click', () => document.getElementById('exportMenu').style.display = 'none');

    $('.btn-eliminar').on('click', function() {
        $('#nombre-eliminar').text($(this).data('nombre'));
        const url = "{{ route('employee.laboratories.destroy', ':id') }}";
        $('#form-eliminar').attr('action', url.replace(':id', $(this).data('id')));
        document.getElementById('modalEliminar').style.setProperty('display', 'flex', 'important');
    });
});
</script>
@endsection
