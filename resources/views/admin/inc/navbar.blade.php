<header class="sticky top-0 bg-white border-b border-slate-200 z-10 h-16 flex items-center px-4 md:px-6 gap-4 shrink-0">

    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-slate-700 p-1 rounded-md transition-colors">
        <i class="fas fa-bars text-lg"></i>
    </button>

    <div class="flex-1">
        <p class="text-sm font-semibold text-slate-800">{{ $pageTitle ?? 'Dashboard' }}</p>
    </div>

    <div class="flex items-center gap-2">
        <button class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="fas fa-bell text-sm"></i>
        </button>

        <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
            <div class="w-8 h-8 bg-violet-700 rounded-full flex items-center justify-center shrink-0">
                <span class="text-white text-xs font-semibold">
                    {{ strtoupper(substr(auth()->guard('admin')->user()?->name ?? 'A', 0, 1)) }}
                </span>
            </div>
            <p class="hidden sm:block text-xs font-medium text-slate-700 max-w-[100px] truncate">
                {{ auth()->guard('admin')->user()?->name ?? 'Admin' }}
            </p>
        </div>
    </div>
</header>
