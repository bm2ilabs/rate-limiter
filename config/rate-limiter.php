<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for multi-period rate limiting.
    | You can define limits for different time periods (minute, hour, day).
    |
    */

    // Rate limits for different periods
    'limits' => [
        // Default per-minute rate limit
        'minute' => env('RATE_LIMIT_PER_MINUTE', 60),

        // Hourly rate limit
        'hour' => env('RATE_LIMIT_PER_HOUR', 1000),

        // Daily rate limit
        'day' => env('RATE_LIMIT_PER_DAY', 10000),
    ],

    // Header names for different periods
    'headers' => [
        'minute' => [
            'limit' => 'X-RateLimit-Limit',
            'remaining' => 'X-RateLimit-Remaining',
        ],
        'hour' => [
            'limit' => 'X-RateLimit-Limit-Hour',
            'remaining' => 'X-RateLimit-Remaining-Hour',
        ],
        'day' => [
            'limit' => 'X-RateLimit-Limit-Day',
            'remaining' => 'X-RateLimit-Remaining-Day',
        ],
    ],

    // Custom identifiers for rate limiting
    'identifiers' => [
        // Use these methods to customize the rate limit identifier
        // Supported: 'ip', 'auth_id', 'custom'
        'default' => 'ip',

        // If using 'custom', specify the request parameter or header to use
        'custom_parameter' => 'api_key',
    ],
];
