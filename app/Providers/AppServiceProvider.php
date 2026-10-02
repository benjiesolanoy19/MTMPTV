<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        foreach ([
            'view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles',
            'view applications', 'create applications', 'manage applications', 'view franchises', 'manage franchises',
            'view permits', 'manage permits', 'view renewals', 'manage renewals', 'view violations', 'manage violations',
            'view reports', 'view my reports', 'view notifications', 'manage users',
            'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications',
            'vehicle owner portal', 'vehicle owner vehicles', 'vehicle owner applications', 'vehicle owner permits', 'vehicle owner franchises', 'vehicle owner renewals', 'vehicle owner violations', 'vehicle owner notifications',
        ] as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }

    /**
     * Register the named rate limiters used by the guest auth routes.
     *
     * Both limiters are keyed by IP address so a locked-out client cannot be
     * triggered by another user sharing a connection. Limits come from
     * config('services.throttle') so they can be tuned per environment.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(
                config('services.throttle.login_decay_minutes'),
                config('services.throttle.login_max_attempts')
            )->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(
                config('services.throttle.register_decay_minutes'),
                config('services.throttle.register_max_attempts')
            )->by($request->ip());
        });
    }
}
