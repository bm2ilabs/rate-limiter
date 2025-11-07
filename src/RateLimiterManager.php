<?php

namespace Amineware\RateLimiter;

use Illuminate\Support\Facades\RateLimiter;

class RateLimiterManager
{
    /**
     * The rate limiter keys for different periods.
     *
     * @var array
     */
    protected $limiters = [
        'minute' => 'throttle-minute',
        'hour' => 'throttle-hour',
        'day' => 'throttle-day',
    ];

    /**
     * Get the number of remaining attempts for a given period and key.
     */
    public function remaining(string $period, string $key): int
    {
        $limiterKey = $this->getLimiterKey($period, $key);
        $maxAttempts = config("rate-limiter.limits.{$period}", 60);

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return 0;
        }

        return $maxAttempts - RateLimiter::attempts($limiterKey);
    }

    /**
     * Determine if the given key has too many attempts.
     */
    public function tooManyAttempts(string $period, string $key, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($this->getLimiterKey($period, $key), $maxAttempts);
    }

    /**
     * Get the number of attempts for the given key.
     */
    public function attempts(string $period, string $key): int
    {
        return RateLimiter::attempts($this->getLimiterKey($period, $key));
    }

    /**
     * Increment the counter for a given key for a given period.
     */
    public function hit(string $period, string $key, int $decaySeconds = 60): void
    {
        $decayMinutes = match ($period) {
            'hour' => 60,
            'day' => 1440,
            default => 1,  // minute
        };

        RateLimiter::hit($this->getLimiterKey($period, $key), $decaySeconds);
    }

    /**
     * Clear the attempts for the given key.
     */
    public function clear(string $period, string $key): void
    {
        RateLimiter::clear($this->getLimiterKey($period, $key));
    }

    /**
     * Get the full limiter key for a given period and key.
     */
    protected function getLimiterKey(string $period, string $key): string
    {
        $prefix = $this->limiters[$period] ?? $this->limiters['minute'];
        $periodPrefix = $period === 'minute' ? '' : "{$period}:";

        return "{$prefix}:{$periodPrefix}{$key}";
    }
}
