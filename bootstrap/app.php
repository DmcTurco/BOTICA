<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias para verificar que hay una caja abierta antes de acceder a órdenes
        $middleware->alias([
            'cash.open'    => \App\Http\Middleware\EnsureCashRegisterIsOpen::class,
            'branch.admin' => \App\Http\Middleware\EnsureEmployeeIsBranchAdmin::class,
            'privilege'    => \App\Http\Middleware\EnsureEmployeeHasPrivilege::class,
            'subscription' => \App\Http\Middleware\EnsureSubscriptionIsActive::class,
        ]);

        // Recuerda la URL de cada listado para que "Volver" conserve búsqueda, filtros y página
        $middleware->web(append: [
            \App\Http\Middleware\RememberListUrl::class,
        ]);

        // Usuario ya logueado que entra al login: va al inicio de su panel (/company/home...), no a "/"
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            $routeType = $request->routeType();

            return $routeType ? route($routeType . '.home') : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
