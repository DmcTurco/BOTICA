<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BOTICA — Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        @keyframes blobMorph {
            0%,100% { border-radius:60% 40% 30% 70% / 60% 30% 70% 40%; }
            25%      { border-radius:40% 60% 55% 45% / 45% 55% 45% 55%; }
            50%      { border-radius:30% 60% 70% 40% / 50% 60% 30% 60%; }
            75%      { border-radius:55% 45% 40% 60% / 35% 65% 35% 65%; }
        }
        @keyframes floatUp {
            0%,100% { transform:translateY(0) rotate(0deg);  }
            50%      { transform:translateY(-28px) rotate(8deg); }
        }
        @keyframes floatDown {
            0%,100% { transform:translateY(0) rotate(0deg);  }
            50%      { transform:translateY(20px) rotate(-6deg); }
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(22px); }
            to   { opacity:1; transform:translateY(0);    }
        }
        @keyframes shimmer {
            0%   { transform:translateX(-120%) skewX(-20deg); }
            100% { transform:translateX(250%)  skewX(-20deg); }
        }
        @keyframes pulseRing {
            0%   { transform:scale(1);   opacity:.5; }
            100% { transform:scale(2);   opacity:0;  }
        }
        @keyframes gradBG {
            0%,100% { background-position:0% 50%;   }
            50%      { background-position:100% 50%; }
        }

        body {
            background: linear-gradient(135deg,#052e16 0%,#14532d 45%,#0f172a 100%);
            background-size: 300% 300%;
            animation: gradBG 12s ease infinite;
        }

        .blob { position:absolute; filter:blur(72px); pointer-events:none; }
        .blob-1 { width:480px;height:480px; background:rgba(34,197,94,.2);  top:-120px;   left:-120px;  animation:blobMorph 12s ease-in-out infinite; }
        .blob-2 { width:540px;height:540px; background:rgba(16,185,129,.15); bottom:-160px;right:-160px; animation:blobMorph 16s ease-in-out 4s infinite reverse; }
        .blob-3 { width:300px;height:300px; background:rgba(74,222,128,.15); top:50%;      left:35%;     animation:blobMorph 10s ease-in-out 8s infinite; }

        .fi { position:absolute; display:flex; align-items:center; justify-content:center;
              background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:12px; pointer-events:none; }
        .fi-1  { width:42px;height:42px; top:10%;   left:8%;  animation:floatUp   6s ease-in-out infinite; }
        .fi-2  { width:48px;height:48px; top:20%;   right:9%; animation:floatDown 8s ease-in-out 1s infinite; }
        .fi-3  { width:36px;height:36px; top:55%;   left:6%;  animation:floatUp   7s ease-in-out 2.5s infinite; }
        .fi-4  { width:44px;height:44px; bottom:22%;right:8%; animation:floatDown 9s ease-in-out 4s infinite; }
        .fi-5  { width:34px;height:34px; bottom:32%;left:14%; animation:floatUp   8s ease-in-out 3.5s infinite; }
        .fi-6  { width:38px;height:38px; top:72%;   right:15%;animation:floatDown 7s ease-in-out 2s infinite; }

        .glass {
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .logo-pulse { position:relative; }
        .logo-pulse::before {
            content:''; position:absolute; inset:-5px; border-radius:20px;
            background:rgba(34,197,94,.3);
            animation:pulseRing 2.4s ease-out infinite;
        }

        .fu1 { animation:fadeUp .7s ease-out .05s both; }
        .fu2 { animation:fadeUp .7s ease-out .15s both; }
        .fu3 { animation:fadeUp .7s ease-out .25s both; }
        .fu4 { animation:fadeUp .7s ease-out .35s both; }
        .fu5 { animation:fadeUp .7s ease-out .45s both; }
        .fu6 { animation:fadeUp .7s ease-out .55s both; }

        .input-wrap { position:relative; display:flex; align-items:center; }
        .input-icon {
            position:absolute; left:0; top:0; bottom:0;
            width:2.75rem; display:flex; align-items:center; justify-content:center;
            pointer-events:none; color:#94a3b8; font-size:.875rem;
        }
        .inp {
            width:100%; padding:.75rem 1rem .75rem 2.75rem;
            font-size:.875rem; border-radius:.75rem;
            border:1.5px solid #e2e8f0; background:#f8fafc;
            color:#1e293b; outline:none;
            transition: border-color .25s, box-shadow .25s, background .25s;
        }
        .inp:focus {
            border-color:#16a34a;
            box-shadow:0 0 0 3px rgba(22,163,74,.14);
            background:#fff;
        }
        .inp::placeholder { color:#94a3b8; }
        .inp-pr { padding-right:2.75rem; }

        .eye-btn {
            position:absolute; right:0; top:0; bottom:0;
            width:2.75rem; display:flex; align-items:center; justify-content:center;
            color:#94a3b8; cursor:pointer; background:none; border:none;
            transition:color .2s;
        }
        .eye-btn:hover { color:#475569; }

        .btn-login {
            position:relative; overflow:hidden;
            width:100%; padding:.82rem 1rem;
            background:linear-gradient(135deg,#16a34a,#15803d);
            color:#fff; font-weight:600; font-size:.875rem;
            border:none; border-radius:.75rem; cursor:pointer;
            transition: transform .25s, box-shadow .25s;
        }
        .btn-login:hover {
            transform:translateY(-2px);
            box-shadow:0 12px 30px rgba(22,163,74,.4);
        }
        .btn-login:active { transform:translateY(0); }
        .btn-login::after {
            content:'';
            position:absolute; top:0; left:0; width:35%; height:100%;
            background:linear-gradient(to right,transparent,rgba(255,255,255,.25),transparent);
            animation:shimmer 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 overflow-hidden relative">

    {{-- Blobs de fondo --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    {{-- Iconos flotantes --}}
    <div class="fi fi-1"><i class="fas fa-pills text-emerald-300 text-sm"></i></div>
    <div class="fi fi-2"><i class="fas fa-heartbeat text-green-300"></i></div>
    <div class="fi fi-3"><i class="fas fa-flask text-teal-300 text-xs"></i></div>
    <div class="fi fi-4"><i class="fas fa-stethoscope text-emerald-300 text-sm"></i></div>
    <div class="fi fi-5"><i class="fas fa-capsules text-green-300 text-xs"></i></div>
    <div class="fi fi-6"><i class="fas fa-syringe text-teal-300 text-xs"></i></div>

    {{-- Card --}}
    <div class="glass relative z-10 w-full max-w-md rounded-3xl p-8 md:p-10"
         style="box-shadow:0 32px 80px rgba(5,46,22,.55)">

        {{-- Logo --}}
        <div class="fu1 flex flex-col items-center mb-8">
            <div class="logo-pulse w-16 h-16 bg-linear-to-br from-emerald-500 to-green-700 rounded-2xl flex items-center justify-center mb-4 shadow-lg"
                 style="box-shadow:0 8px 24px rgba(22,163,74,.35)">
                <i class="fas fa-plus text-white text-2xl"></i>
            </div>
            <p class="text-2xl font-bold text-slate-800 tracking-tight">BOTICA</p>
            <span class="mt-1.5 text-xs font-semibold px-3 py-0.5 bg-emerald-100 text-emerald-700 rounded-full tracking-wide">Panel de Empresa</span>
        </div>

        {{-- Heading --}}
        <div class="fu2 mb-6">
            <h1 class="text-xl font-bold text-slate-800">Bienvenido de nuevo</h1>
            <p class="text-slate-500 text-sm mt-1">Ingresa tus credenciales para acceder</p>
        </div>

        {{-- Alertas --}}
        @if ($errors->any())
        <div class="fu2 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3.5 mb-5 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
            @endforeach
        </div>
        @endif

        @if (session('status'))
        <div class="fu2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-3.5 mb-5 text-sm">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            {{-- Email --}}
            <div class="fu3">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="inp" placeholder="correo@farmacia.com">
                </div>
            </div>

            {{-- Contraseña --}}
            <div class="fu4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Contraseña</label>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="pwd" required
                           class="inp inp-pr" placeholder="••••••••">
                    <button type="button" class="eye-btn" onclick="togglePwd()">
                        <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            {{-- Recordar --}}
            <div class="fu5 flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember"
                       class="w-4 h-4 rounded border-slate-300" style="accent-color:#16a34a">
                <label for="remember" class="text-sm text-slate-600 cursor-pointer select-none">Recordar sesión</label>
            </div>

            {{-- Submit --}}
            <div class="fu6 pt-1">
                <button type="submit" class="btn-login">
                    Iniciar sesión &nbsp;<i class="fas fa-arrow-right text-xs"></i>
                </button>
            </div>
        </form>

        <p class="fu6 text-center text-xs text-slate-400 mt-6">BOTICA · Sistema Farmacéutico v1.0</p>
    </div>

    <script>
    function togglePwd() {
        const i = document.getElementById('pwd');
        const e = document.getElementById('eyeIcon');
        i.type = i.type === 'password' ? 'text' : 'password';
        e.className = i.type === 'password' ? 'fas fa-eye text-sm' : 'fas fa-eye-slash text-sm';
    }
    </script>
</body>
</html>
