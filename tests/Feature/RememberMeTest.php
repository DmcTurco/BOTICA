<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/**
 * En producción, FortifyServiceProvider elige el panel por la URL con la que arranca la app (/employee/login...).
 * En los tests la app arranca con "/", así que aquí se aplica la misma configuración del panel y el login
 * se envía a la ruta de Fortify: se prueba el mismo circuito (Fortify → guard del panel → cookie).
 */
function usePanel(string $guard): void
{
    config([
        'fortify.guard' => $guard,
        'fortify.home'  => "/{$guard}/home",
    ]);
}

/** Usuario de cada panel con una contraseña conocida */
function rememberUser(string $guard): array
{
    $user = match ($guard) {
        'admin'    => Admin::create(['name' => 'Admin', 'email' => 'recordar-admin@example.com', 'password' => 'secreto123']),
        'company'  => Company::create(['name' => 'Botica Recordar', 'email' => 'recordar-company@example.com', 'password' => 'secreto123']),
        'employee' => Employee::create(['company_id' => 1, 'branch_id' => 1, 'role_id' => Role::BRANCH_ADMIN, 'name' => 'Empleado',
            'email' => 'recordar-employee@example.com', 'password' => 'secreto123']),
    };

    return [$user, $user->email];
}

it('"Recordar sesión" mantiene la sesión aunque se pierda la sesión del navegador', function (string $guard) {
    usePanel($guard);
    [$user, $email] = rememberUser($guard);
    $recaller = Auth::guard($guard)->getRecallerName();

    // Con la casilla marcada: guarda el token y entrega la cookie de "recordarme"
    $login = $this->post('/login', ['email' => $email, 'password' => 'secreto123', 'remember' => 'on']);
    $login->assertRedirect();
    expect($login->headers->get('Location'))->toContain("/{$guard}/")->not->toContain('login');

    $cookie = $login->getCookie($recaller);
    expect($cookie)->not->toBeNull()
        ->and($user->fresh()->remember_token)->not->toBeNull();

    // Se pierde la sesión (cerró el navegador): solo con la cookie vuelve a entrar sin contraseña
    $this->flushSession();
    Auth::forgetGuards();

    $home = $this->withCookie($recaller, $cookie->getValue())->get("/{$guard}/home");
    expect($home->isRedirect() ? $home->headers->get('Location') : 'ok')->not->toContain('login');
    expect(Auth::guard($guard)->id())->toBe($user->getKey())
        ->and(Auth::guard($guard)->viaRemember())->toBeTrue();
})->with(['admin', 'company', 'employee']);

it('sin marcar "Recordar sesión" no entrega la cookie de recordarme', function (string $guard) {
    usePanel($guard);
    [, $email] = rememberUser($guard);

    $login = $this->post('/login', ['email' => $email, 'password' => 'secreto123']);
    $login->assertRedirect();
    expect($login->headers->get('Location'))->toContain("/{$guard}/")->not->toContain('login');

    expect($login->getCookie(Auth::guard($guard)->getRecallerName()))->toBeNull();
})->with(['admin', 'company', 'employee']);
