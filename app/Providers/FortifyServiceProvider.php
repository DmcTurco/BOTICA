<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\MyApp;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $routeType = request()->routeType();
        if ($routeType) {
            config([
                'fortify.prefix' => $routeType,
                'fortify.guard' => $routeType,
                'fortify.home' => '/' . $routeType . '/home',
                'fortify.login' => '/' . $routeType . '/login',
                'fortify.logout' => '/' . $routeType . '/logout',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::loginView(function () {
            if (request()->routeType() == MyApp::ADMINS_SUBDIR) {
                return view('admin.pages.login');
            } else if (request()->routeType() == MyApp::COMPANY_SUBDIR) {
                return view('company.pages.login');
            }else if (request()->routeType() == MyApp::EMPLOYEE_SUBDIR) {
                return view('company.pages.login');
            }
            return view('auth.login');
        });
    }
}
