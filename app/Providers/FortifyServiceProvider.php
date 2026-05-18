<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\MyApp;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios de la aplicación.
     */
    public function register(): void
    {
        $routeType = request()->routeType();
        if ($routeType) {
            config([
                'fortify.prefix' => $routeType,
                'fortify.guard'  => $routeType,
                'fortify.home'   => '/' . $routeType . '/home',
                'fortify.login'  => '/' . $routeType . '/login',
                'fortify.logout' => '/' . $routeType . '/logout',
            ]);
        }
    }

    /**
     * Arranca los servicios de la aplicación.
     */
    public function boot(): void
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        /**
         * Autenticación personalizada para que el email sea case-insensitive.
         * PostgreSQL distingue mayúsculas por defecto en WHERE email = ?,
         * por lo que se usa LOWER() en ambos lados de la comparación.
         */
        Fortify::authenticateUsing(function (Request $request) {
            // Mapa guard → modelo Eloquent correspondiente
            $modelMap = [
                MyApp::ADMINS_SUBDIR   => Admin::class,
                MyApp::COMPANY_SUBDIR  => Company::class,
                MyApp::EMPLOYEE_SUBDIR => Employee::class,
            ];

            $guard      = config('fortify.guard');
            $modelClass = $modelMap[$guard] ?? null;

            if (!$modelClass) {
                return null;
            }

            // Normalizar email: quitar espacios y convertir a minúsculas
            $email = strtolower(trim($request->input(Fortify::username(), '')));

            // Buscar el usuario con comparación case-insensitive
            $user = $modelClass::whereRaw('LOWER(email) = ?', [$email])->first();

            // Validar contraseña
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::loginView(function () {
            if (request()->routeType() == MyApp::ADMINS_SUBDIR) {
                return view('admin.pages.login');
            } elseif (request()->routeType() == MyApp::COMPANY_SUBDIR) {
                return view('company.pages.login');
            } elseif (request()->routeType() == MyApp::EMPLOYEE_SUBDIR) {
                return view('employee.pages.login');
            }
            return view('auth.login');
        });
    }
}
