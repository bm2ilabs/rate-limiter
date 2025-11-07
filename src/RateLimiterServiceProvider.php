<?php

namespace Amineware\RateLimiter;

use Amineware\RateLimiter\Middleware\MultiPeriodRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/rate-limiter.php', 'rate-limiter'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/rate-limiter.php' => config_path('rate-limiter.php'),
        ], 'config');

        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('multi-rate-limit', MultiPeriodRateLimiter::class);

        // Register the rate limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Get rate limit configuration
        $minuteLimit = Config::get('rate-limiter.limits.minute', 60);
        $hourLimit = Config::get('rate-limiter.limits.hour', 1000);
        $dayLimit = Config::get('rate-limiter.limits.day', 10000);

        // Minute-based rate limiter
        RateLimiter::for('throttle-minute', function (Request $request) use ($minuteLimit) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($minuteLimit)->by($key);
        });

        // Hour-based rate limiter
        RateLimiter::for('throttle-hour', function (Request $request) use ($hourLimit) {
            $key = $request->user()?->id ?: $request->ip();

            // Using perMinute with 60 minutes decay (1 hour)
            return Limit::perMinute($hourLimit)
                ->by('hour:'.$key);
        });

        // Day-based rate limiter
        RateLimiter::for('throttle-day', function (Request $request) use ($dayLimit) {
            $key = $request->user()?->id ?: $request->ip();

            // Using perDay method if available (Laravel 8+)
            if (method_exists(Limit::class, 'perDay')) {
                return Limit::perDay($dayLimit)->by('day:'.$key);
            }

            // Fallback for older Laravel versions
            return Limit::perMinute($dayLimit)
                ->by('day:'.$key);
        });
    }
}
