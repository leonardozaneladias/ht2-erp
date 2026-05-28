<?php

declare(strict_types=1);

use App\Providers\RateLimiterServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    $provider = new RateLimiterServiceProvider($this->app);
    $provider->boot();
});

test('limiter api está registrado', function (): void {
    expect(RateLimiter::limiter('api'))->toBeCallable();
});

test('limiter login está registrado', function (): void {
    expect(RateLimiter::limiter('login'))->toBeCallable();
});

test('limiter api retorna limite de 120 por minuto', function (): void {
    $request = Request::create('/test', 'GET');
    $limiter = RateLimiter::limiter('api');
    $limit = $limiter($request);

    expect($limit->maxAttempts)->toBe(120);
});

test('limiter login retorna limite de 5 por minuto', function (): void {
    $request = Request::create('/test', 'POST');
    $request->merge(['email' => 'test@example.com']);
    $limiter = RateLimiter::limiter('login');
    $limit = $limiter($request);

    expect($limit->maxAttempts)->toBe(5);
});
