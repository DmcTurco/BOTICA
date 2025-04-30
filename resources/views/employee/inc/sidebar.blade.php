<aside class="sidenav  bg-gray-600 navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4 "
    id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="">
            <img src="{{ asset('assets/svg/logo.svg') }}" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-1 text-white font-weight-bold">Media Player</span>
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}" href="{{ route('employee.home') }}">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="ni ni-tv-2 text-primary text-lg opacity-10"></i>
                    </div>
                    <span class="nav-link-text text-white font-weight-bold ms-1">Dashboard</span>
                </a>
            </li>

            {{-- Reservaciones --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.video.*') ? 'active' : '' }}" href="">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="ni ni-calendar-grid-58 text-warning text-lg opacity-10"></i>
                    </div>
                    <span class="nav-link-text text-white font-weight-bold ms-1">Videos</span>
                </a>
            </li>

            {{-- Cuartos --}}
            {{-- <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}" href="">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="ni ni-building text-success text-lg opacity-10"></i>
                    </div>
                    <span class="nav-link-text text-white font-weight-bold ms-1">Cuartos</span>
                </a>
            </li> --}}



        </ul>
    </div>
</aside>
