@extends('employee/layouts/base')

@section('title', 'Unidades de Medida')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Unidades de Medida</h1>
            <p class="text-sm text-slate-500 mt-0.5">Tabletas, cajas, frascos, gramos... usadas en productos y presentaciones</p>
        </div>
        <button type="button" id="btnNueva"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nueva Unidad
        </button>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.units.index') }}" method="GET" class="flex gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o abreviatura..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2">
                <i class="fas fa-search text-xs"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Abrev.</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Código SUNAT</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Uso</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($units as $unit)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-medium text-slate-800">{{ $unit->name }}</td>
                        <td class="px-5 py-3 text-center text-xs font-mono text-slate-600">{{ $unit->abbreviation }}</td>
                        <td class="px-5 py-3 text-center text-xs font-mono text-slate-600">{{ $unit->sunat_code }}</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-600">{{ $unit->productos_count }} prod. · {{ $unit->presentaciones_count }} pres.</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $unit->status ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $unit->status ? 'Activa' : 'Inactiva' }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" class="btn-editar w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-600"
                                        data-id="{{ $unit->id }}" data-name="{{ $unit->name }}" data-abbr="{{ $unit->abbreviation }}"
                                        data-sunat="{{ $unit->sunat_code }}" data-status="{{ $unit->status }}" title="Editar">
                                    <i class="fas fa-pen text-xs pointer-events-none"></i>
                                </button>
                                <button type="button" class="btn-eliminar w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600"
                                        data-id="{{ $unit->id }}" data-nombre="{{ $unit->name }}" title="Eliminar">
                                    <i class="fas fa-trash text-xs pointer-events-none"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">No hay unidades registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($units->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $units->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal crear / editar --}}
<div id="modalUnidad" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form id="formUnidad" method="POST" action="{{ route('employee.units.store') }}" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <input type="hidden" name="_method" id="metodo" value="POST">
        <h3 class="text-base font-semibold text-slate-800" id="tituloModal">Nueva unidad</h3>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="uName" required maxlength="50" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Abreviatura <span class="text-red-500">*</span></label>
                <input type="text" name="abbreviation" id="uAbbr" required maxlength="10" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg uppercase focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                <select name="status" id="uStatus" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="1">Activa</option><option value="0">Inactiva</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Código SUNAT (catálogo 03) <span class="text-red-500">*</span></label>
            <select name="sunat_code" id="uSunat" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                @foreach($sunatCodes as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="flex gap-3 justify-end pt-2">
            <button type="button" onclick="cerrarModales()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Guardar</button>
        </div>
    </form>
</div>

{{-- Modal eliminar --}}
<div id="modalEliminar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-trash text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Confirmar eliminación</h3>
        </div>
        <p class="text-slate-600 text-sm mb-6">¿Seguro que deseas eliminar la unidad <strong id="nombre-eliminar" class="text-slate-800"></strong>?</p>
        <div class="flex gap-3 justify-end">
            <button onclick="cerrarModales()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <form id="form-eliminar" action="" method="POST" class="inline">@csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Eliminar</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const abrir = id => document.getElementById(id).style.setProperty('display', 'flex', 'important');
function cerrarModales() {
    ['modalUnidad', 'modalEliminar'].forEach(id => document.getElementById(id).style.setProperty('display', 'none', 'important'));
}
$(function () {
    $('#btnNueva').on('click', function () {
        $('#formUnidad').attr('action', "{{ route('employee.units.store') }}");
        $('#metodo').val('POST');
        $('#tituloModal').text('Nueva unidad');
        $('#uName, #uAbbr').val('');
        $('#uStatus').val('1'); $('#uSunat').val('NIU');
        abrir('modalUnidad');
    });
    $('.btn-editar').on('click', function () {
        const d = $(this).data();
        $('#formUnidad').attr('action', "{{ route('employee.units.update', ':id') }}".replace(':id', d.id));
        $('#metodo').val('PUT');
        $('#tituloModal').text('Editar unidad');
        $('#uName').val(d.name); $('#uAbbr').val(d.abbr);
        $('#uStatus').val(String(d.status)); $('#uSunat').val(d.sunat);
        abrir('modalUnidad');
    });
    $('.btn-eliminar').on('click', function () {
        $('#nombre-eliminar').text($(this).data('nombre'));
        $('#form-eliminar').attr('action', "{{ route('employee.units.destroy', ':id') }}".replace(':id', $(this).data('id')));
        abrir('modalEliminar');
    });
});
</script>
@endsection
