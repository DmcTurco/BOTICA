{{-- Aviso de la mensualidad (paneles de empresa y empleado). Recibe $company.
     Se muestra: prueba o mensualidad por terminar (ámbar) y vencida en días de gracia (rojo). --}}
@php
    $subStatus = $company?->subscriptionStatus();
    $subDays   = $company?->subscriptionDaysLeft();
    $isTrial   = $company?->isTrialCoverage();
    $what      = $isTrial ? 'El periodo de prueba gratis' : 'La mensualidad del servicio';

    $showSoon    = in_array($subStatus, [\App\Models\Company::SUB_DUE_SOON, \App\Models\Company::SUB_TRIAL], true)
                   && $subDays <= config('billing.warning_days');
    $showOverdue = $subStatus === \App\Models\Company::SUB_OVERDUE;
@endphp

@if($showSoon || $showOverdue)
<div class="shrink-0 px-4 py-2 text-sm flex items-center gap-2 border-b {{ $showOverdue ? 'bg-red-50 border-red-200 text-red-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}">
    <i class="fas {{ $showOverdue ? 'fa-circle-exclamation' : 'fa-triangle-exclamation' }}"></i>
    <span class="flex-1">
        @if($showOverdue)
            {{ $what }} {{ $isTrial ? 'terminó' : 'venció' }} el {{ $company->paid_until->format('d/m/Y') }}.
            El acceso se bloqueará el <strong>{{ $company->subscriptionBlocksOn()->format('d/m/Y') }}</strong> si no se registra el pago.
        @elseif($subDays === 0)
            {{ $what }} {{ $isTrial ? 'termina' : 'vence' }} <strong>hoy</strong>.
        @else
            {{ $what }} {{ $isTrial ? 'termina' : 'vence' }} en <strong>{{ $subDays }} {{ $subDays === 1 ? 'día' : 'días' }}</strong> ({{ $company->paid_until->format('d/m/Y') }}).
        @endif
        @if($showPaymentInfo ?? false)
            {{ config('billing.payment_info') }}
        @endif
    </span>
</div>
@endif
