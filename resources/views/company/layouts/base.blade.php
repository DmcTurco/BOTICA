<!DOCTYPE html>
<html>

@include('company/inc/head')

<body class="g-sidenav-show   bg-gray-100">
    <div class="min-height-300 bg-secondary position-absolute w-100"></div>

    @include('company/inc/navbar')
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <main class="main-content position-relative border-radius-lg ">
            @include('company/inc/sidebar')
            <div id="content" class="main-content">
                <div class="layout-px-spacing">
                    @if (isset($pageTitle))
                        <div class="page-header">
                            <div class="page-title">
                                <h3>{{ $pageTitle }}</h3>
                            </div>
                        </div>
                    @endif

                    @yield('content-area')

                </div>
            </div>
        </main>
    </div>

    @include('company/inc/foot')

</body>

</html>
