<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BOTICA — Acceso Empleado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 antialiased">
<div class="min-h-screen flex">

    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">

            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 bg-sky-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-plus text-white text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-lg leading-none">BOTICA</p>
                    <p class="text-xs text-slate-400">Portal Empleado</p>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-1">Hola, empleado</h1>
            <p class="text-slate-500 text-sm mb-8">Ingresa tus credenciales para acceder a tu panel</p>

            @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white
                                  focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                           placeholder="empleado@botica.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contraseña</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white
                                  focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                           placeholder="••••••••">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-slate-300 text-sky-600">
                    <label for="remember" class="text-sm text-slate-600 cursor-pointer select-none">Recordar sesión</label>
                </div>
                <button type="submit"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors text-sm">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>

    <div class="hidden lg:flex w-[440px] bg-sky-600 flex-col items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full border-2 border-white"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full border-2 border-white"></div>
        </div>
        <div class="relative text-center text-white">
            <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center mx-auto mb-8">
                <i class="fas fa-user-nurse text-white text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold mb-3">Portal de Empleados</h2>
            <p class="text-sky-100 text-sm mb-10 leading-relaxed">
                Accede a tus tareas, registra ventas<br>y gestiona el inventario del día.
            </p>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/10 rounded-xl p-4">
                    <i class="fas fa-cash-register text-2xl mb-2 block"></i>
                    <p class="text-xs text-sky-100">Ventas</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <i class="fas fa-boxes-stacked text-2xl mb-2 block"></i>
                    <p class="text-xs text-sky-100">Stock</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <i class="fas fa-calendar-days text-2xl mb-2 block"></i>
                    <p class="text-xs text-sky-100">Turnos</p>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
