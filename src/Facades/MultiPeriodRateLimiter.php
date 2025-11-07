<?php

namespace Amineware\RateLimiter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static int remaining(string $period, string $key)
 * @method static bool tooManyAttempts(string $period, string $key, int $maxAttempts)
 * @method static int attempts(string $period, string $key)
 * @method static void hit(string $period, string $key, int $decaySeconds = 60)
 * @method static void clear(string $period, string $key)
 *
 * @see \Amineware\RateLimiter\RateLimiterManager
 */
class MultiPeriodRateLimiter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amineware-rate-limiter';
    }
}
