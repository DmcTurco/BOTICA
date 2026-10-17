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
        // Token CSRF vencido (419 "Page Expired"): pasa con una página abierta mucho tiempo o
        // cuando la sesión se cerró en otra pestaña. En vez de la página de error, se resuelve solo.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'La página estuvo abierta mucho tiempo y venció. Revisa los datos y vuelve a intentarlo.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            $panel = $request->routeType();
            $login = $panel ? url($panel . '/login') : url('/');

            // Cerrar sesión: igual se quería salir, se va directo al login
            if (str_ends_with($request->path(), 'logout')) {
                if ($panel) {
                    \Illuminate\Support\Facades\Auth::guard($panel)->logout();
                }

                return redirect($login);
            }

            // Ingresar con la página de login vieja: vuelve al login con el correo ya escrito
            if (str_ends_with($request->path(), 'login')) {
                return redirect($login)
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'La página de ingreso estuvo abierta mucho tiempo. Vuelve a escribir tu contraseña.']);
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password']))
                ->with('error', $message);
        });
    })->create();
