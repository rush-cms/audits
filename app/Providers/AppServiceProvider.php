<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        RateLimiter::for('pdf-generation', function (): Limit {
            return Limit::perMinute(config('audits.queue.pdf_concurrency', 3));
        });

        RateLimiter::for('screenshot-capture', function (): Limit {
            return Limit::perMinute(config('audits.queue.screenshot_concurrency', 5));
        });
    }
}
