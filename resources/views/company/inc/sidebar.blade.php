<aside id="sidebar"
       class="fixed top-0 left-0 h-full w-64 z-30 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300"
       style="background:linear-gradient(180deg,#052e16 0%,#064e3b 100%)">

    {{-- Logo --}}
    <div class="flex items-center gap-3 h-16 px-5 shrink-0" style="border-bottom:1px solid rgba(255,255,255,.08)">
        <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center shrink-0 shadow-lg shadow-emerald-900/50">
            <i class="fas fa-plus text-white text-sm"></i>
        </div>
        <div>
            <p class="font-bold text-white text-sm leading-none tracking-wide">BOTICA</p>
            <p class="text-xs mt-0.5" style="color:rgba(167,243,208,.5)">Panel Empresa</p>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">

        <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color:rgba(167,243,208,.4)">Principal</p>

        <a href="{{ route('company.home') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                  {{ Route::currentRouteName() == 'company.home'
                     ? 'bg-emerald-500/20 text-emerald-300 shadow-inner'
                     : 'text-emerald-100/60 hover:bg-white/5 hover:text-white' }}">
            <i class="fas fa-house w-4 text-center text-sm"></i>
            Dashboard
        </a>

        <div class="pt-5 pb-1">
            <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color:rgba(167,243,208,.4)">Ventas</p>
        </div>

        <a href="{{ route('company.sales.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                  {{ Route::currentRouteName() == 'company.sales.index'
                     ? 'bg-emerald-500/20 text-emerald-300 shadow-inner'
                     : 'text-emerald-100/60 hover:bg-white/5 hover:text-white' }}">
            <i class="fas fa-cash-register w-4 text-center text-sm"></i>
            Ventas
        </a>

        <div class="pt-5 pb-1">
            <p class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color:rgba(167,243,208,.4)">Catálogo</p>
        </div>

        <a href="{{ route('company.products.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                  {{ in_array(Route::currentRouteName(), ['company.products.index','company.products.create','company.products.edit'])
                     ? 'bg-emerald-500/20 text-emerald-300 shadow-inner'
                     : 'text-emerald-100/60 hover:bg-white/5 hover:text-white' }}">
            <i class="fas fa-pills w-4 text-center text-sm"></i>
            Productos
        </a>

        <a href="{{ route('company.categories.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                  {{ in_array(Route::currentRouteName(), ['company.categories.index','company.categories.create','company.categories.edit'])
                     ? 'bg-emerald-500/20 text-emerald-300 shadow-inner'
                     : 'text-emerald-100/60 hover:bg-white/5 hover:text-white' }}">
            <i class="fas fa-tags w-4 text-center text-sm"></i>
            Categorías
        </a>

        <a href="{{ route('company.laboratories.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                  {{ str_starts_with(Route::currentRouteName() ?? '', 'company.laboratories')
                     ? 'bg-emerald-500/20 text-emerald-300 shadow-inner'
                     : 'text-emerald-100/60 hover:bg-white/5 hover:text-white' }}">
            <i class="fas fa-flask w-4 text-center text-sm"></i>
            Laboratorios
        </a>
    </nav>

    {{-- Logout --}}
    <div class="px-3 py-3" style="border-top:1px solid rgba(255,255,255,.08)">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all"
                    style="color:rgba(167,243,208,.5)"
                    onmouseover="this.style.background='rgba(239,68,68,.12)';this.style.color='#fca5a5'"
                    onmouseout="this.style.background='';this.style.color='rgba(167,243,208,.5)'">
                <i class="fas fa-right-from-bracket w-4 text-center text-sm"></i>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
