<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        foreach ([
            'view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles',
            'view applications', 'create applications', 'manage applications', 'view franchises', 'manage franchises',
            'view permits', 'manage permits', 'view renewals', 'manage renewals', 'view violations', 'manage violations',
            'view reports', 'view my reports', 'view notifications', 'manage users',
            'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications',
        ] as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
