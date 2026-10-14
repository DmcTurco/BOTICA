@extends('admin/layouts/base')

@section('title', 'Suscripciones')
@section('main-class', 'overflow-hidden')

@php
    use App\Models\Company;

    // Filtros por estado, en el orden en que importan para cobrar
    $filters = [
        ''                     => 'Todas',
        Company::SUB_SUSPENDED => 'Bloqueadas',
        Company::SUB_OVERDUE   => 'Vencidas',
        Company::SUB_DUE_SOON  => 'Por vencer',
        Company::SUB_TRIAL     => 'En prueba',
        Company::SUB_ACTIVE    => 'Al día',
        Company::SUB_EXEMPT    => 'Gratis',
    ];
    $current = request('estado', '');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Suscripciones</h1>
        <p class="text-sm text-slate-500 mt-0.5">Cuánto paga cada empresa, hasta cuándo puede usar el sistema y sus pagos</p>
    </div>

    {{-- Filtros por estado --}}
    <div class="shrink-0 flex flex-wrap gap-2">
        @foreach($filters as $value => $label)
        @php $count = $value === '' ? $counts->sum() : ($counts[$value] ?? 0); @endphp
        <a href="{{ route('admin.subscriptions.index', $value === '' ? [] : ['estado' => $value]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors
                  {{ $current === $value ? 'bg-violet-700 border-slate-200 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }} <span class="{{ $current === $value ? 'text-white' : 'text-slate-400' }}">{{ $count }}</span>
        </a>
        @endforeach
    </div>

    {{-- Tabla (ocupa el alto restante; el scroll va adentro) --}}
    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($companies->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-16">
            <i class="fas fa-money-bill-wave text-slate-300 text-2xl mb-2"></i>
            <p class="text-sm text-slate-500">No hay empresas en este estado</p>
        </div>
        @else
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Empresa</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Situación</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Puede usar hasta</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Paga</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Vence</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($companies as $company)
                    @php
                        $status = $company->subscriptionStatus();
                        $days   = $company->subscriptionDaysLeft();
                        $owes   = in_array($status, [Company::SUB_OVERDUE, Company::SUB_SUSPENDED], true);
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-800">{{ $company->name }}</p>
                            <p class="text-xs text-slate-400">{{ $company->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @include('admin/inc/subscription-badge', ['company' => $company])
                            <p class="text-xs mt-1 {{ $owes ? 'text-red-600' : 'text-slate-400' }}">
                                @if($status === Company::SUB_EXEMPT)
                                    No se le cobra
                                @elseif($days > 0)
                                    {{ $status === Company::SUB_TRIAL ? 'Prueba: quedan' : 'Quedan' }} {{ $days }} {{ $days === 1 ? 'día' : 'días' }}
                                @elseif($days === 0)
                                    {{ $status === Company::SUB_TRIAL ? 'La prueba termina hoy' : 'Vence hoy' }}
                                @elseif($status === Company::SUB_OVERDUE)
                                    Debe {{ -$days }} {{ -$days === 1 ? 'día' : 'días' }} · se bloquea el {{ $company->subscriptionBlocksOn()->format('d/m') }}
                                @else
                                    Bloqueada hace {{ -$days - config('billing.grace_days') }} {{ (-$days - config('billing.grace_days')) === 1 ? 'día' : 'días' }}
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $company->paid_until?->format('d/m/Y') ?? 'Sin límite' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $company->paid_until ? 'S/ ' . number_format($company->monthly_fee, 2) . ' al mes' : 'Gratis' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            @if($company->paid_until)
                                {{ $company->billing_cycle === Company::CYCLE_MONTH_END ? 'Fin de cada mes' : 'Cada día ' . $company->paid_until->day }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.subscriptions.show', $company) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                                      {{ $owes ? 'bg-violet-700 hover:bg-violet-800 text-white' : 'border border-slate-200 text-slate-600 hover:bg-violet-50' }}">
                                {{ $owes ? 'Registrar pago' : ($status === Company::SUB_EXEMPT ? 'Configurar cobro' : 'Ver') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
