<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
    }
}
