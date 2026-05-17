<aside id="sidebar"
       class="fixed top-0 left-0 h-screen w-64 bg-emerald-950 flex flex-col z-30 transition-transform duration-300 lg:translate-x-0 -translate-x-full">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-emerald-900 shrink-0">
        <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center shrink-0">
            <i class="fas fa-plus text-white text-sm"></i>
        </div>
        <div>
            <p class="text-sm font-bold text-white leading-none">BOTICA</p>
            <p class="text-xs text-slate-400 mt-0.5">Sistema Farmacéutico</p>
        </div>
    </div>

    {{-- Navegación --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">

        {{-- Principal --}}
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Principal</p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('company.home') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('company.home') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-emerald-900 hover:text-white' }}">
                        <i class="fas fa-gauge-high w-4 text-center shrink-0"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>

        {{-- Administración --}}
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Administración</p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('company.branches.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('company.branches.*') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-emerald-900 hover:text-white' }}">
                        <i class="fas fa-building w-4 text-center shrink-0"></i>
                        Sedes
                    </a>
                </li>
                <li>
                    <a href="{{ route('company.employees.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('company.employees.*') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-emerald-900 hover:text-white' }}">
                        <i class="fas fa-user-tie w-4 text-center shrink-0"></i>
                        Empleados
                    </a>
                </li>
            </ul>
        </div>

    </nav>

    {{-- Logout --}}
    <div class="shrink-0 border-t border-emerald-900 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:bg-red-900/40 hover:text-red-400 transition-colors">
                <i class="fas fa-right-from-bracket w-4 text-center shrink-0"></i>
                Cerrar sesión
            </button>
        </form>
    </div>

</aside>
