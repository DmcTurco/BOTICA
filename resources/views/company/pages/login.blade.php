<!DOCTYPE html>
<html lang="es">
@include('company/inc/head')

<body class="">
    <div class="container position-sticky z-index-sticky top-0">
        <div class="row">
            <div class="col-12">
                <!-- Navbar -->
                <nav
                    class="navbar navbar-expand-lg blur border-radius-lg top-0 z-index-3 shadow position-absolute mt-4 py-2 start-0 end-0 mx-4">
                    <div class="container-fluid">
                        <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3 " href="#">
                            MediTrack - Sistema de Gestión Farmacéutica
                        </a>
                        <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false"
                            aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon mt-2">
                                <span class="navbar-toggler-bar bar1"></span>
                                <span class="navbar-toggler-bar bar2"></span>
                                <span class="navbar-toggler-bar bar3"></span>
                            </span>
                        </button>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>
        </div>
    </div>
    <main class="main-content mt-0">
        <section>
            <div class="page-header min-vh-100">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column mx-lg-0 mx-auto">
                            <div class="card card-plain">
                                <div class="card-header pb-0 text-start">
                                    <h4 class="font-weight-bolder">Iniciar Sesión</h4>
                                    <p class="mb-0">Ingrese su correo y contraseña para acceder</p>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('login') }}" >
                                        @csrf
                                        <div class="mb-3">
                                            <input type="email" class="form-control form-control-lg" name="email"
                                                placeholder="Correo electrónico" aria-label="Email">
                                        </div>
                                        <div class="mb-3">
                                            <input type="password" class="form-control form-control-lg" name="password"
                                                placeholder="Contraseña" aria-label="Password">
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="rememberMe">
                                            <label class="form-check-label" for="rememberMe">Recordar sesión</label>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit"
                                                class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">Ingresar</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                    <p class="mb-4 text-sm mx-auto">
                                        ¿No tiene una cuenta?
                                        <a href="javascript:;" class="text-primary text-gradient font-weight-bold">Registrarse</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 end-0 text-center justify-content-center flex-column">
                            <div class="position-relative bg-gradient-primary h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
                                style="background-image: url('../assets/img/farmacia.jpg'); background-size: cover;">
                                <span class="mask bg-gradient-primary opacity-6"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="100" height="100" style="margin-bottom: 20px;">
                                    <g fill="#ffffff">
                                        <path d="M120 20c-8.3 0-15 6.7-15 15v45H60c-8.3 0-15 6.7-15 15v50c0 8.3 6.7 15 15 15h45v45c0 8.3 6.7 15 15 15h50c8.3 0 15-6.7 15-15v-45h45c8.3 0 15-6.7 15-15v-50c0-8.3-6.7-15-15-15h-45V35c0-8.3-6.7-15-15-15h-50z"/>
                                    </g>
                                </svg>
                                <h4 class="mt-5 text-white font-weight-bolder position-relative">Sistema de Gestión Farmacéutica</h4>
                                <p class="text-white position-relative">Administre su inventario y ventas de manera eficiente</p>
                                <div class="row mt-5 w-100">
                                    <div class="col-4 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="#ffffff">
                                            <path d="M19 6h-4V2H9v4H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-8 11c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"/>
                                        </svg>
                                        <h6 class="text-white mt-2">Inventario</h6>
                                    </div>
                                    <div class="col-4 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="#ffffff">
                                            <path d="M19 3h-4.2c-.4-1.2-1.5-2-2.8-2-1.3 0-2.4.8-2.8 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.6 0 1 .4 1 1s-.4 1-1 1-1-.4-1-1 .4-1 1-1zm-2 14l-4-4 1.4-1.4L9 13.2l6.6-6.6L17 8l-7 7z"/>
                                        </svg>
                                        <h6 class="text-white mt-2">Recetas</h6>
                                    </div>
                                    <div class="col-4 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="#ffffff">
                                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zm-6-7h-4v-1h4v1zm-8 3h12v1H6v-1zm0-2h12v1H6v-1z"/>
                                        </svg>
                                        <h6 class="text-white mt-2">Ventas</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        :root {
            --bs-primary: #2D8B61;
            --bs-primary-rgb: 45, 139, 97;
        }
        
        .btn-primary {
            background-color: #2D8B61;
            border-color: #2D8B61;
        }
        
        .btn-primary:hover {
            background-color: #1A5D40;
            border-color: #1A5D40;
        }
        
        .text-primary {
            color: #2D8B61 !important;
        }
        
        .bg-gradient-primary {
            background-image: linear-gradient(195deg, #2D8B61 0%, #1A5D40 100%);
        }
    </style>
</body>

</html>