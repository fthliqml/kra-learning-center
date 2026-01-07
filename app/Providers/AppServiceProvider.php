<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

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
        // Force all generated URLs (asset(), route(), etc.) to use HTTPS when in production
        // or when the explicit override variable is set. This prevents mixed content when
        // the app is accessed via HTTPS tunnels like ngrok while the framework thinks the
        // request is HTTP behind a proxy.
        if ($this->app->environment('production') || env('APP_FORCE_HTTPS')) {
            try {
                URL::forceScheme('https');
            } catch (\Throwable $e) {
                // Silently ignore in case URL facade not ready during some CLI contexts.
            }
        }

        // Blade role-based directives
        Blade::if('role', function ($role) {
            $user = Auth::user();
            $method = 'hasRole';
            return $user && is_callable([$user, $method]) && $user->{$method}($role);
        });

        Blade::if('anyrole', function (...$roles) {
            $roles = collect($roles)
                ->flatMap(fn($r) => is_array($r) ? $r : explode(',', $r))
                ->map(fn($r) => trim($r))
                ->filter()
                ->values()
                ->all();
            $user = Auth::user();
            $method = 'hasAnyRole';
            return $user && is_callable([$user, $method]) && $user->{$method}($roles);
        });

        Blade::if('allroles', function (...$roles) {
            $roles = collect($roles)
                ->flatMap(fn($r) => is_array($r) ? $r : explode(',', $r))
                ->map(fn($r) => trim($r))
                ->filter()
                ->values()
                ->all();
            $user = Auth::user();
            $method = 'hasAllRoles';
            return $user && is_callable([$user, $method]) && $user->{$method}($roles);
        });

        // Shorthand: @r('admin','leader') or @r('admin,leader')
        Blade::if('r', function (...$roles) {
            $roles = collect($roles)
                ->flatMap(fn($r) => is_array($r) ? $r : explode(',', $r))
                ->map(fn($r) => trim($r))
                ->filter()
                ->values()
                ->all();
            $user = Auth::user();
            $method = 'hasAnyRole';
            return $user && is_callable([$user, $method]) && $user->{$method}($roles);
        });

        Paginator::defaultView('vendor.pagination.tailwind');
    }
}
