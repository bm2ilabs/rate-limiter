# Amineware Rate Limiter

A comprehensive Laravel package for implementing multi-period rate limiting in your application. This package allows you to easily set and enforce rate limits for different time intervals (minute, hour, day) and automatically includes these limits in your API response headers.

## Features

- Rate limiting for multiple time periods (minute, hour, day)
- Custom response headers for each period
- Configurable limits through environment variables or config file
- Easy to use middleware
- Support for different identifiers (IP, authenticated user, custom parameter)
- Compatible with Laravel 8.x, 9.x, 10.x, and 11.x

## Installation

You can install the package via composer:

```bash
composer require amineware/rate-limiter
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Amineware\RateLimiter\RateLimiterServiceProvider"
```

This will create a `rate-limiter.php` file in your config directory. You can customize the rate limits and other settings in this file.

## Basic Usage

Add the middleware to your routes:

```php
// In routes/api.php
Route::middleware('multi-rate-limit')->group(function () {
    // Your API routes here
});
```

Or apply it to specific routes:

```php
Route::get('/endpoint', 'YourController@method')->middleware('multi-rate-limit');
```

## Response Headers

When using this middleware, your API responses will include the following headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Limit-Hour: 1000
X-RateLimit-Remaining-Hour: 999
X-RateLimit-Limit-Day: 10000
X-RateLimit-Remaining-Day: 9999
```

If a rate limit is exceeded, the response will include:

```
Retry-After: <seconds until reset>
X-RateLimit-Reset: <timestamp>
```

## Advanced Usage

### Using the Facade

You can use the facade to manually check or manipulate rate limits:

```php
use Amineware\RateLimiter\Facades\MultiPeriodRateLimiter;

// Check remaining attempts
$remaining = MultiPeriodRateLimiter::remaining('minute', $userId);

// Check if too many attempts
if (MultiPeriodRateLimiter::tooManyAttempts('hour', $userId, 100)) {
    // Handle rate limit exceeded
}

// Increment the counter
MultiPeriodRateLimiter::hit('day', $userId);

// Clear rate limits for a key
MultiPeriodRateLimiter::clear('minute', $userId);
```

### Custom Identifiers

By default, the package uses the authenticated user ID or IP address as the identifier. You can customize this in the config file:

```php
// In config/rate-limiter.php
'identifiers' => [
    'default' => 'custom',
    'custom_parameter' => 'api_key',
],
```

## Environment Variables

You can set rate limits through environment variables:

```
RATE_LIMIT_PER_MINUTE=60
RATE_LIMIT_PER_HOUR=1000
RATE_LIMIT_PER_DAY=10000
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.