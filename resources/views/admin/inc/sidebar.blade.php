<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white border-r border-slate-200 z-30 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="flex items-center gap-3 h-16 px-5 border-b border-slate-200 shrink-0">
        <div class="w-8 h-8 bg-violet-700 rounded-lg flex items-center justify-center shrink-0">
            <i class="fas fa-shield-halved text-white text-sm"></i>
        </div>
        <div>
            <p class="font-bold text-slate-800 text-sm leading-none">BOTICA</p>
            <p class="text-xs text-slate-400 mt-0.5">Panel Admin</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Administración</p>

        <a href="{{ route('admin.home') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors mb-0.5
                  {{ Route::currentRouteName() == 'admin.home' ? 'bg-violet-50 text-violet-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800' }}">
            <i class="fas fa-house w-4 text-center text-sm"></i>
            Dashboard
        </a>

        <div class="pt-4 pb-1">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Sistema</p>
        </div>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors mb-0.5 text-slate-600 hover:bg-slate-50 hover:text-slate-800">
            <i class="fas fa-building w-4 text-center text-sm"></i>
            Empresas
        </a>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors mb-0.5 text-slate-600 hover:bg-slate-50 hover:text-slate-800">
            <i class="fas fa-users w-4 text-center text-sm"></i>
            Usuarios
        </a>
    </nav>

    <div class="px-3 py-3 border-t border-slate-200">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors">
                <i class="fas fa-right-from-bracket w-4 text-center text-sm"></i>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
