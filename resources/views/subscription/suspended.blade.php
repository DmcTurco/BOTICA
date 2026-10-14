<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BOTICA — Servicio suspendido</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Pantalla de bloqueo por falta de pago de la mensualidad (SuspendedController) --}}
<body class="bg-slate-50 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 bg-red-50 border-b border-red-200 flex items-start gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-lock text-red-500"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-800">Servicio suspendido</h1>
                <p class="text-sm text-slate-600 mt-0.5">{{ $company->name }}</p>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4 text-sm text-slate-700">
            <p>
                {{ $company->isTrialCoverage() ? 'El periodo de prueba gratis terminó' : 'La mensualidad venció' }}
                el <strong>{{ $company->paid_until->format('d/m/Y') }}</strong>
                y pasaron los días de gracia, por eso el acceso está bloqueado.
            </p>

            @if($isCompany)
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 space-y-1">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Para reactivar</p>
                @if($company->monthly_fee > 0)
                <p>Mensualidad: <strong>S/ {{ number_format($company->monthly_fee, 2) }}</strong></p>
                @endif
                <p>{{ $paymentInfo }}</p>
            </div>
            <p class="text-xs text-slate-500">Apenas se registre tu pago, el sistema vuelve a funcionar para ti y tus empleados.</p>
            @else
            <p class="text-slate-600">Avisa al responsable de la botica para que regularice el pago. Tus datos están a salvo.</p>
            @endif
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
            <a href="{{ url()->current() }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                <i class="fas fa-rotate-right mr-1"></i> Ya pagué, volver a intentar
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
