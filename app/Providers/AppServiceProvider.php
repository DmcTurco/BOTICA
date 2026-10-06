<?php

namespace App\Providers;

use App\MyApp;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Services\Sunat\GreenterGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Canal de envío a SUNAT: hoy Greenter directo; cambiar aquí si se contrata un proveedor
        $this->app->bind(SunatGateway::class, GreenterGateway::class);

        $requestUri = $this->app->request->getRequestUri();
        Request::macro('routeType', function () use ($requestUri) {
            if (preg_match("#^/" . MyApp::ADMINS_SUBDIR . "/#", $requestUri)) {
                return MyApp::ADMINS_SUBDIR;
            } else if (preg_match("#^/" . MyApp::COMPANY_SUBDIR . "/#", $requestUri)) {
                return MyApp::COMPANY_SUBDIR;
            } else if (preg_match("#^/" . MyApp::EMPLOYEE_SUBDIR . "/#", $requestUri)) {
                return MyApp::EMPLOYEE_SUBDIR;
            } else {
                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.simple-custom');
    }
}
