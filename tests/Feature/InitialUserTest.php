<?php

use App\Models\Admin;
use App\Support\InitialUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('en local, sin contraseña en el .env, la cuenta inicial usa la de desarrollo', function () {
    expect(Hash::check(config('initial_users.dev_password'), Admin::first()->password))->toBeTrue()
        ->and(Hash::check('0000', Admin::first()->password))->toBeFalse();
});

it('usa el correo y la contraseña definidos en el .env', function () {
    config(['initial_users.admin.email' => 'soporte@botica.pe', 'initial_users.admin.password' => 'ClaveSegura2026']);

    $data = InitialUser::attributes('admin');

    expect($data['email'])->toBe('soporte@botica.pe')
        ->and(Hash::check('ClaveSegura2026', $data['password']))->toBeTrue();
});

it('en producción exige una contraseña fuerte para cada cuenta inicial', function () {
    app()->detectEnvironment(fn () => 'production');

    config(['initial_users.company.password' => null]);
    expect(fn () => InitialUser::attributes('company'))->toThrow(RuntimeException::class, 'INITIAL_COMPANY_PASSWORD');

    config(['initial_users.company.password' => '0000']);
    expect(fn () => InitialUser::attributes('company'))->toThrow(RuntimeException::class);

    config(['initial_users.company.password' => 'Farmacia2026']);
    expect(Hash::check('Farmacia2026', InitialUser::attributes('company')['password']))->toBeTrue();
});

it('una contraseña fuerte tiene 10 caracteres o más, letras y números', function () {
    expect(InitialUser::isStrong('Farmacia2026'))->toBeTrue()
        ->and(InitialUser::isStrong('Corta1'))->toBeFalse()
        ->and(InitialUser::isStrong('solamenteletras'))->toBeFalse()
        ->and(InitialUser::isStrong('1234567890'))->toBeFalse();
});
