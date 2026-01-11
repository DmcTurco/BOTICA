<!DOCTYPE html>
<html>

@include('company/inc/head')

<body class="{{ $class ?? '' }}">
    
    <div class="wrapper">
        @include('company/inc/sidebar')
        
        <div class="main-panel">
            @include('company/inc/navbar')
            @yield('content-area')
          
        </div>
    </div>
    
    @include('company/inc/fixed-plugin')

</body>

@include('company/inc/foot')
@stack('scripts')

</html>