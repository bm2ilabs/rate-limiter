<?php

namespace Amineware\RateLimiter\Tests;

use Amineware\RateLimiter\Middleware\MultiPeriodRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Orchestra\Testbench\TestCase;

class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('rate-limiter.limits.minute', 10);
        Config::set('rate-limiter.limits.hour', 100);
        Config::set('rate-limiter.limits.day', 1000);

        // Clear any existing rate limiters
        RateLimiter::clear('amineware-minute:127.0.0.1');
        RateLimiter::clear('amineware-hour:hour:127.0.0.1');
        RateLimiter::clear('amineware-day:day:127.0.0.1');
    }

    protected function getPackageProviders($app)
    {
        return ['Amineware\RateLimiter\RateLimiterServiceProvider'];
    }

    /** @test */
    public function it_adds_rate_limit_headers_to_response()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = new MultiPeriodRateLimiter();

        $response = $middleware->handle($request, function () {
            return new Response('Test Response');
        });

        // Check if the headers are present
        $this->assertEquals(10, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(9, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals(100, $response->headers->get('X-RateLimit-Limit-Hour'));
        $this->assertEquals(99, $response->headers->get('X-RateLimit-Remaining-Hour'));
        $this->assertEquals(1000, $response->headers->get('X-RateLimit-Limit-Day'));
        $this->assertEquals(999, $response->headers->get('X-RateLimit-Remaining-Day'));
    }

    /** @test */
    public function it_decrements_remaining_attempts_on_each_request()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = new MultiPeriodRateLimiter();

        // First request
        $response1 = $middleware->handle($request, function () {
            return new Response('Test Response');
        });

        // Second request
        $response2 = $middleware->handle($request, function () {
            return new Response('Test Response');
        });

        // Check if the remaining attempts are decremented
        $this->assertEquals(9, $response1->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals(8, $response2->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function it_returns_429_response_when_limit_is_exceeded()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = new MultiPeriodRateLimiter();

        // Manually exhaust the limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('amineware-minute:127.0.0.1');
        }

        // This request should exceed the limit
        $response = $middleware->handle($request, function () {
            return new Response('This should not be reached');
        });

        // Check if it's a 429 response
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many attempts', $response->getContent());
        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }
}