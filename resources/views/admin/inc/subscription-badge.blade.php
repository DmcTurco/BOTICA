{{-- Etiqueta del estado de la suscripción de una empresa. Recibe $company. --}}
@php
    $subStatus = $company->subscriptionStatus();
    $subClass  = [
        \App\Models\Company::SUB_EXEMPT    => 'bg-slate-100 text-slate-600',
        \App\Models\Company::SUB_TRIAL     => 'bg-sky-100 text-sky-700',
        \App\Models\Company::SUB_ACTIVE    => 'bg-emerald-100 text-emerald-700',
        \App\Models\Company::SUB_DUE_SOON  => 'bg-amber-100 text-amber-700',
        \App\Models\Company::SUB_OVERDUE   => 'bg-red-100 text-red-700',
        \App\Models\Company::SUB_SUSPENDED => 'bg-red-100 text-red-700',
    ][$subStatus];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $subClass }}">
    {{ \App\Models\Company::SUB_LABELS[$subStatus] }}
</span>
