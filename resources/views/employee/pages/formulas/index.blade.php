@extends('employee/layouts/base')

@section('title', 'Fórmulas Magistrales')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Fórmulas Magistrales</h1>
            <p class="text-sm text-slate-500 mt-0.5">Recetas del laboratorio: insumos, cantidades y producto final</p>
        </div>
        <a href="{{ route('employee.formulas.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nueva Fórmula
        </a>
    </div>

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

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.formulas.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Código, nombre o forma farmacéutica...">
                </div>
                <select name="status"
                        class="sm:w-44 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activas</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivas</option>
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar','status']))
                <a href="{{ route('employee.formulas.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Fórmulas
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $formulas->total() }} registros</span>
            </p>
        </div>

        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fórmula</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Forma</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rinde</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Insumos</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Venta POS</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($formulas as $formula)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('employee.formulas.show', $formula) }}" class="font-medium text-slate-800 hover:text-emerald-600">{{ $formula->name }}</a>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $formula->code }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600 hidden md:table-cell">{{ $formula->pharmaceutical_form ?: '—' }}</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-700">
                            {{ rtrim(rtrim(number_format($formula->yield_quantity, 2), '0'), '.') }} {{ $formula->yieldUnit?->abbreviation }}
                        </td>
                        <td class="px-5 py-3 text-center hidden sm:table-cell">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">{{ $formula->ingredients_count }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($formula->sell_in_pos)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700" title="{{ $formula->product?->name }}">
                                    <i class="fas fa-cash-register text-[9px] mr-1"></i> Sí
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">No</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $formula->status ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $formula->status ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_PRODUCIR_FORMULAS) && $formula->status)
                                <a href="{{ route('employee.productions.create', ['formula' => $formula->id]) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" title="Preparar lote">
                                    <i class="fas fa-flask-vial text-xs"></i>
                                </a>
                                @endif
                                <a href="{{ route('employee.formulas.show', $formula) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="Ver">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('employee.formulas.edit', $formula) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-600 transition-colors" title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                <button type="button" data-id="{{ $formula->id }}" data-nombre="{{ $formula->name }}"
                                        class="btn-eliminar w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors" title="Eliminar">
                                    <i class="fas fa-trash text-xs pointer-events-none"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-vial text-3xl"></i>
                                <p class="text-sm">No hay fórmulas registradas</p>
                                <a href="{{ route('employee.formulas.create') }}" class="text-emerald-600 text-sm hover:underline">Registrar primera fórmula</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($formulas->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $formulas->withQueryString()->links() }}
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
            ¿Seguro que deseas eliminar la fórmula <strong id="nombre-eliminar" class="text-slate-800"></strong>?
        </p>
        <p class="text-slate-500 text-xs mb-6">Los lotes ya preparados se conservan en el historial.</p>
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
    $('.btn-eliminar').on('click', function() {
        $('#nombre-eliminar').text($(this).data('nombre'));
        const url = "{{ route('employee.formulas.destroy', ':id') }}";
        $('#form-eliminar').attr('action', url.replace(':id', $(this).data('id')));
        document.getElementById('modalEliminar').style.setProperty('display', 'flex', 'important');
    });
});
</script>
@endsection
