@extends('employee/layouts/base')

@section('title', 'Productos para Reposición')
@section('main-padding', 'p-2 md:p-3')

@php $canBuy = auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_VER_COMPRAS); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Productos para Reposición</h1>
            <p class="text-sm text-slate-500 mt-0.5">Stock igual o por debajo del mínimo en tu sede</p>
        </div>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.replenishment.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select name="category" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <select name="laboratory" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">Todos los laboratorios</option>
                @foreach($laboratories as $l)<option value="{{ $l->id }}" {{ request('laboratory') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2">
                <i class="fas fa-search text-xs"></i> Filtrar
            </button>
        </form>
    </div>

    <form action="{{ route('employee.purchase-orders.create') }}" method="GET" class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">
        <div class="px-5 py-3 border-b border-slate-200 shrink-0 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-slate-800">Por reponer <span class="ml-2 text-xs font-normal text-slate-400">{{ $rows->total() }} productos</span></p>
            @if($canBuy && $rows->count())
            <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg"><i class="fas fa-file-circle-check text-[10px]"></i> Orden de compra con los marcados</button>
            @endif
        </div>
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        @if($canBuy)<th class="px-5 py-3 w-10"><input type="checkbox" id="marcarTodos" class="rounded border-slate-300 text-emerald-600" checked></th>@endif
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Laboratorio</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Mínimo</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sugerido</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                    <tr class="hover:bg-slate-50">
                        @if($canBuy)<td class="px-5 py-3"><input type="checkbox" class="marcar rounded border-slate-300 text-emerald-600" name="products[{{ $row->product_code }}]" value="{{ $row->suggested }}" checked></td>@endif
                        <td class="px-5 py-3">
                            <p class="text-xs font-medium text-slate-800">{{ $row->product->name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $row->product_code }} @if($row->product->category) · {{ $row->product->category->name }} @endif</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600 hidden md:table-cell">{{ $row->product->laboratory?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $row->stock_actual <= 0 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700' }}">
                                {{ rtrim(rtrim(number_format($row->stock_actual, 2), '0'), '.') ?: '0' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-xs text-slate-600">{{ $row->stock_minimum }}</td>
                        <td class="px-5 py-3 text-center text-xs font-semibold text-emerald-700">+{{ rtrim(rtrim(number_format($row->suggested, 2), '0'), '.') }} {{ $row->product->unit?->abbreviation }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($canBuy)
                            <a href="{{ route('employee.purchases.create', ['product' => $row->product_code]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                                <i class="fas fa-cart-plus text-[10px]"></i> Comprar
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-14 text-center text-slate-400">
                        <i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i>
                        <p class="text-sm">Todo el stock está por encima del mínimo</p>
                        <p class="text-xs mt-1">Define el stock mínimo de cada producto para recibir alertas aquí</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $rows->links() }}</div>
        @endif
    </form>
</div>
@endsection

@section('scripts')
<script>
const todos = document.getElementById('marcarTodos');
if (todos) todos.addEventListener('change', () => document.querySelectorAll('.marcar').forEach(c => c.checked = todos.checked));
</script>
@endsection
