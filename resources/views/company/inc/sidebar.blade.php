<div class="sidebar" data-color="white" data-active-color="danger">
    <div class="logo">
        <a href="{{ route('company.home') }}" class="simple-text logo-mini">
            <div class="logo-image-small">
                <img src="{{ asset('assets/template/img/logo-small.png') }}">
            </div>
        </a>
        <a href="{{ route('company.home') }}" class="simple-text logo-normal">
            {{ __('Mi Empresa') }}
        </a>
    </div>
    <div class="sidebar-wrapper">
        <ul class="nav">
            <li class="{{ ($elementActive ?? '') == 'dashboard' ? 'active' : '' }}">
                <a href="{{ route('company.home') }}">
                    <i class="nc-icon nc-bank"></i>
                    <p>{{ __('Dashboard') }}</p>
                </a>
            </li>
            <li class="{{ in_array($elementActive ?? '', ['profile', 'user']) ? 'active' : '' }}">
                <a data-toggle="collapse" aria-expanded="true" href="#laravelExamples">
                    <i class="nc-icon"><img src="{{ asset('assets/template/img/laravel.svg') }}"></i>
                    <p>
                        {{ __('Laravel examples') }}
                        <b class="caret"></b>
                    </p>
                </a>
                <div class="collapse show" id="laravelExamples">
                    <ul class="nav">
                        <li class="{{ ($elementActive ?? '') == 'profile' ? 'active' : '' }}">
                            <a href="#">
                                <span class="sidebar-mini-icon">{{ __('UP') }}</span>
                                <span class="sidebar-normal">{{ __(' User Profile ') }}</span>
                            </a>
                        </li>
                        <li class="{{ ($elementActive ?? '') == 'user' ? 'active' : '' }}">
                            <a href="#">
                                <span class="sidebar-mini-icon">{{ __('U') }}</span>
                                <span class="sidebar-normal">{{ __(' User Management ') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="{{ ($elementActive ?? '') == 'icons' ? 'active' : '' }}">
                <a href="#">
                    <i class="nc-icon nc-diamond"></i>
                    <p>{{ __('Icons') }}</p>
                </a>
            </li>
            <li class="{{ ($elementActive ?? '') == 'map' ? 'active' : '' }}">
                <a href="#">
                    <i class="nc-icon nc-pin-3"></i>
                    <p>{{ __('Maps') }}</p>
                </a>
            </li>
            <li class="{{ ($elementActive ?? '') == 'notifications' ? 'active' : '' }}">
                <a href="#">
                    <i class="nc-icon nc-bell-55"></i>
                    <p>{{ __('Notifications') }}</p>
                </a>
            </li>
            <li class="{{ ($elementActive ?? '') == 'tables' ? 'active' : '' }}">
                <a href="#">
                    <i class="nc-icon nc-tile-56"></i>
                    <p>{{ __('Table List') }}</p>
                </a>
            </li>
            <li class="{{ ($elementActive ?? '') == 'typography' ? 'active' : '' }}">
                <a href="#">
                    <i class="nc-icon nc-caps-small"></i>
                    <p>{{ __('Typography') }}</p>
                </a>
            </li>

        </ul>
    </div>
</div>