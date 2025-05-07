<?php

namespace Amineware\RateLimiter\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class MultiPeriodRateLimiter
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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get user identifier (usually IP or authenticated user ID)
        $key = $request->user()?->id ?: $request->ip();

        // Define user-specific keys for rate limiters
        $minuteKey = $key;
        $hourKey = 'hour:' . $key;
        $dayKey = 'day:' . $key;

        // Get limits from config
        $minuteLimit = Config::get('rate-limiter.limits.minute', 60);
        $hourLimit = Config::get('rate-limiter.limits.hour', 1000);
        $dayLimit = Config::get('rate-limiter.limits.day', 10000);

        // Check if user has exceeded any limits
        foreach ($this->limiters as $period => $limiterName) {
            $limitKey = $period === 'minute' ? $minuteKey : ($period === 'hour' ? $hourKey : $dayKey);
            $limit = $period === 'minute' ? $minuteLimit : ($period === 'hour' ? $hourLimit : $dayLimit);

            if (RateLimiter::tooManyAttempts($limiterName . ':' . $limitKey, $limit)) {
                $seconds = RateLimiter::availableIn($limiterName . ':' . $limitKey);

                return $this->buildTooManyAttemptsResponse($request, $period, $seconds);
            }
        }

        // Increment the attempts for each limiter
        RateLimiter::hit($this->limiters['minute'] . ':' . $minuteKey);
        RateLimiter::hit($this->limiters['hour'] . ':' . $hourKey);
        RateLimiter::hit($this->limiters['day'] . ':' . $dayKey);

        // Process the request
        $response = $next($request);

        // Add rate limit headers
        return $this->addHeaders(
            $response,
            $minuteLimit,
            $hourLimit,
            $dayLimit,
            $this->getRemainingAttempts($this->limiters['minute'] . ':' . $minuteKey, $minuteLimit),
            $this->getRemainingAttempts($this->limiters['hour'] . ':' . $hourKey, $hourLimit),
            $this->getRemainingAttempts($this->limiters['day'] . ':' . $dayKey, $dayLimit)
        );
    }

    /**
     * Add the rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $minuteLimit
     * @param  int  $hourLimit
     * @param  int  $dayLimit
     * @param  int  $minuteRemaining
     * @param  int  $hourRemaining
     * @param  int  $dayRemaining
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(
        Response $response,
        int $minuteLimit,
        int $hourLimit,
        int $dayLimit,
        int $minuteRemaining,
        int $hourRemaining,
        int $dayRemaining
    ): Response {
        $headers = [
            'X-RateLimit-Limit' => $minuteLimit,
            'X-RateLimit-Remaining' => $minuteRemaining,
            'X-RateLimit-Limit-Hour' => $hourLimit,
            'X-RateLimit-Remaining-Hour' => $hourRemaining,
            'X-RateLimit-Limit-Day' => $dayLimit,
            'X-RateLimit-Remaining-Day' => $dayRemaining,
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Get the number of remaining attempts for a given rate limiter.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function getRemainingAttempts(string $key, int $maxAttempts): int
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return 0;
        }

        return $maxAttempts - RateLimiter::attempts($key);
    }

    /**
     * Build the response for when too many attempts have been made.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $period
     * @param  int  $seconds
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildTooManyAttemptsResponse(Request $request, string $period, int $seconds): Response
    {
        $message = sprintf(
            'Too many attempts. Please try again in %s %s.',
            $seconds > 60 ? ceil($seconds / 60) . ' minutes' : $seconds . ' seconds',
            $period
        );

        $response = response()->json(['message' => $message], 429);

        return $response->header('Retry-After', $seconds)
            ->header('X-RateLimit-Reset', now()->addSeconds($seconds)->getTimestamp());
    }
}