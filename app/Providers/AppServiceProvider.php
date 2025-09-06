<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

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
        // Improve session handling for Stripe checkouts
        $this->configureSessionForCheckouts();
    }

    /**
     * Configure session settings to improve Stripe checkout experience
     */
    private function configureSessionForCheckouts(): void
    {
        // Set session cookie to be more persistent for checkout flows
        Config::set('session.same_site', 'lax');
        Config::set('session.secure', request()->isSecure());
        Config::set('session.http_only', true);

        // Extend session lifetime during checkout processes
        if (request()->is('subscription/*') || request()->has('session_id')) {
            Config::set('session.lifetime', 240); // 4 hours for checkout flows
        }
    }
}
