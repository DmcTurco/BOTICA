@extends('admin/layouts/base')

@section('title', 'Suscripción — ' . $company->name)

@php
    $status   = $company->subscriptionStatus();
    $days     = $company->subscriptionDaysLeft();
    $isFree   = $company->paid_until === null;
    $plan     = old('plan', $isFree ? 'free' : 'monthly');
    $cycle    = old('billing_cycle', $company->billing_cycle);
    $dayWord  = fn ($n) => $n === 1 ? 'día' : 'días';
@endphp

@section('content-area')
<div class="space-y-3">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.subscriptions.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Suscripción · {{ $company->name }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->email }}</p>
        </div>
    </div>

    {{-- Resumen: en qué situación está hoy --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Situación hoy</p>
            @include('admin/inc/subscription-badge', ['company' => $company])
            <p class="text-sm text-slate-600 mt-2">
                @if($status === \App\Models\Company::SUB_EXEMPT)
                    No se le cobra. Usa el sistema sin límite.
                @elseif($status === \App\Models\Company::SUB_TRIAL)
                    Usa el sistema gratis; la prueba termina {{ $days === 0 ? 'hoy' : "en {$days} " . $dayWord($days) }}.
                @elseif($days >= 0)
                    Pagado. Vence {{ $days === 0 ? 'hoy' : "en {$days} " . $dayWord($days) }}.
                @elseif($status === \App\Models\Company::SUB_OVERDUE)
                    Debe {{ -$days }} {{ $dayWord(-$days) }}. Si no paga, se bloquea el {{ $company->subscriptionBlocksOn()->format('d/m/Y') }}.
                @else
                    Bloqueada: no puede entrar. Debe {{ -$days }} días.
                @endif
            </p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Puede usar el sistema hasta</p>
            <p class="text-2xl font-bold text-slate-800">{{ $company->paid_until?->format('d/m/Y') ?? 'Sin límite' }}</p>
            @if($company->paid_until)
            <p class="text-xs text-slate-500 mt-1">+ {{ config('billing.grace_days') }} días de gracia antes de bloquearse</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Paga</p>
            @if($isFree)
            <p class="text-2xl font-bold text-slate-800">Nada</p>
            <p class="text-xs text-slate-500 mt-1">Plan gratis</p>
            @else
            <p class="text-2xl font-bold text-slate-800">S/ {{ number_format($company->monthly_fee, 2) }} <span class="text-sm font-medium text-slate-500">al mes</span></p>
            <p class="text-xs text-slate-500 mt-1">
                {{ $company->billing_cycle === \App\Models\Company::CYCLE_MONTH_END ? 'Vence el último día de cada mes' : 'Vence cada mes el día ' . $company->paid_until->day }}
            </p>
            @endif
        </div>
    </div>

    {{-- ── 1. Plan ─────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('admin.subscriptions.update', $company) }}"
          class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf @method('PUT')
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
            <p class="text-sm font-semibold text-slate-800">1. Plan</p>
            <p class="text-xs text-slate-500">
                {{ $isFree ? 'Hoy no se le cobra. Para empezar a cobrarle, elige "Mensual" y define su prueba gratis.' : 'Cambia el monto, cuándo vence o pásala a gratis.' }}
            </p>
        </div>

        <div class="p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input type="radio" name="plan" value="free" class="mt-1 plan-radio" {{ $plan === 'free' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Gratis</span>
                        <span class="block text-xs text-slate-500">No paga nunca y nunca se bloquea.</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input type="radio" name="plan" value="monthly" class="mt-1 plan-radio" {{ $plan === 'monthly' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Mensual</span>
                        <span class="block text-xs text-slate-500">Paga cada mes. Si no paga, recibe avisos y luego se bloquea.</span>
                    </span>
                </label>
            </div>

            <div id="monthlyFields" class="space-y-4 {{ $plan === 'free' ? 'hidden' : '' }}">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">¿Cuánto paga al mes? (S/)</label>
                        <input type="number" name="monthly_fee" step="0.01" min="0"
                               value="{{ old('monthly_fee', $company->monthly_fee > 0 ? $company->monthly_fee : '') }}" placeholder="80.00"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('monthly_fee') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">¿Cuándo vence cada mes?</label>
                        <select name="billing_cycle" id="billingCycle"
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="{{ \App\Models\Company::CYCLE_DATE }}" {{ $cycle === \App\Models\Company::CYCLE_DATE ? 'selected' : '' }}>El mismo día de cada mes</option>
                            <option value="{{ \App\Models\Company::CYCLE_MONTH_END }}" {{ $cycle === \App\Models\Company::CYCLE_MONTH_END ? 'selected' : '' }}>El último día de cada mes</option>
                        </select>
                    </div>
                    <div>
                        @if($isFree)
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Prueba gratis: desde hoy hasta</label>
                        <input type="date" name="covered_until" id="coveredUntil" min="{{ today()->format('Y-m-d') }}"
                               value="{{ old('covered_until', $trialSuggestion[$cycle]) }}"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('covered_until') border-red-400 @else border-slate-200 @enderror">
                        @else
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Puede usar el sistema hasta</label>
                        <input type="date" name="covered_until"
                               value="{{ old('covered_until', $company->paid_until->format('Y-m-d')) }}"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('covered_until') border-red-400 @else border-slate-200 @enderror">
                        @endif
                    </div>
                </div>

                @if($isFree)
                {{-- Ejemplo que cambia según el ciclo elegido, para que quede claro qué va a pasar --}}
                <div class="rounded-lg bg-sky-50 border border-slate-200 px-4 py-3 text-sm text-slate-700">
                    <p id="cycleExample"></p>
                </div>
                @else
                <p class="text-xs text-slate-500">
                    Cambia esta fecha solo para corregir un error o regalar días. Cuando paga, usa <strong>2. Registrar pago</strong>.
                </p>
                @endif
            </div>
        </div>

        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50">
            <button type="submit"
                    class="px-5 py-2.5 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                {{ $isFree ? 'Guardar plan' : 'Guardar cambios del plan' }}
            </button>
        </div>
    </form>

    {{-- ── 2. Registrar pago ───────────────────────────────────── --}}
    @if($isFree)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4">
        <p class="text-sm font-semibold text-slate-800">2. Registrar pago</p>
        <p class="text-xs text-slate-500 mt-1">Esta empresa es gratis. Para registrarle pagos, primero pásala a plan <strong>Mensual</strong>.</p>
    </div>
    @else
    <form method="POST" action="{{ route('admin.subscriptions.payments.store', $company) }}"
          class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
            <p class="text-sm font-semibold text-slate-800">2. Registrar pago</p>
            <p class="text-xs text-slate-500">
                Cuando te pague, regístralo aquí. Con este pago podrá usar el sistema del
                <strong>{{ $coverFrom->format('d/m/Y') }}</strong> al <strong id="coverTo">{{ $coverage[old('months', 1)] ?? $coverage[1] }}</strong>.
            </p>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">¿Cuántos meses paga?</label>
                <input type="number" name="months" id="payMonths" min="1" max="24" value="{{ old('months', 1) }}"
                       class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('months') border-red-400 @else border-slate-200 @enderror">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Monto recibido (S/)</label>
                <input type="number" name="amount" id="payAmount" step="0.01" min="0" value="{{ old('amount', number_format($company->monthly_fee, 2, '.', '')) }}"
                       class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('amount') border-red-400 @else border-slate-200 @enderror">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha en que pagó</label>
                <input type="date" name="paid_at" value="{{ old('paid_at', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"
                       class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('paid_at') border-red-400 @else border-slate-200 @enderror">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">¿Cómo pagó?</label>
                <select name="method"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                    @foreach($methods as $value => $label)
                    <option value="{{ $value }}" {{ old('method', 'yape') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">N° de operación</label>
                <input type="text" name="operation_number" maxlength="50" value="{{ old('operation_number') }}" placeholder="Opcional"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nota</label>
                <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="Opcional"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50">
            <button type="submit"
                    class="px-5 py-2.5 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <i class="fas fa-check mr-1"></i> Registrar pago
            </button>
        </div>
    </form>
    @endif

    {{-- ── 3. Historial ────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
            <p class="text-sm font-semibold text-slate-800">3. Historial de pagos</p>
        </div>
        @if($payments->isEmpty())
        <p class="px-5 py-8 text-center text-sm text-slate-400">Aún no hay pagos registrados.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Pagó el</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Cubre</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Monto</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Cómo</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Registró</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($payments as $payment)
                    <tr>
                        <td class="px-5 py-3 text-slate-700">{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            del {{ $payment->period_from->format('d/m/Y') }} al {{ $payment->period_to->format('d/m/Y') }}
                            <span class="text-slate-400">({{ $payment->months }} {{ $payment->months === 1 ? 'mes' : 'meses' }})</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">S/ {{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            {{ $methods[$payment->method] ?? $payment->method }}
                            @if($payment->operation_number)<span class="block text-slate-400 font-mono">{{ $payment->operation_number }}</span>@endif
                            @if($payment->notes)<span class="block text-slate-400">{{ $payment->notes }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $payment->admin?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($loop->first)
                            <form method="POST" action="{{ route('admin.subscriptions.payments.destroy', [$company, $payment]) }}"
                                  data-confirm="¿Anular este pago? Podrá usar el sistema solo hasta el {{ $payment->period_from->copy()->subDay()->format('d/m/Y') }}."
                                  data-confirm-title="Anular pago" data-confirm-ok="Anular">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-600">Anular</button>
                            </form>
                            @endif
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

@section('scripts')
<script>
(function () {
    // Plan gratis oculta los campos de la mensualidad
    document.querySelectorAll('.plan-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.getElementById('monthlyFields').classList.toggle('hidden', this.value === 'free' && this.checked);
        });
    });

    @if($isFree)
    // Al activar el cobro: la prueba sugerida depende del ciclo, con un ejemplo de qué pasará
    var suggestion = @json($trialSuggestion);
    var cycle      = document.getElementById('billingCycle');
    var until      = document.getElementById('coveredUntil');

    function fmt(iso) { var p = iso.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; }

    function explain() {
        var d = until.value ? fmt(until.value) : '—';
        document.getElementById('cycleExample').textContent = cycle.value === 'month_end'
            ? 'Usa el sistema gratis desde hoy hasta el ' + d + '. Después paga el último día de cada mes.'
            : 'Usa el sistema gratis desde hoy hasta el ' + d + '. Después paga cada mes ese mismo día.';
    }

    cycle.addEventListener('change', function () { until.value = suggestion[this.value]; explain(); });
    until.addEventListener('input', explain);
    explain();
    @else
    // Registrar pago: monto sugerido = meses × mensualidad, y hasta qué fecha queda cubierta
    var fee      = {{ (float) $company->monthly_fee }};
    var coverage = @json($coverage);
    var months   = document.getElementById('payMonths');
    months.addEventListener('input', function () {
        var m = parseInt(months.value, 10);
        if (m > 0) document.getElementById('payAmount').value = (fee * m).toFixed(2);
        if (coverage[m]) document.getElementById('coverTo').textContent = coverage[m];
    });
    @endif
})();
</script>
@endsection
