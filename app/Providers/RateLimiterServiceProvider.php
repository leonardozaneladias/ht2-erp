<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $r): Limit => Limit::perMinute(120)->by(
            $r->user() !== null ? (string) $r->user()->getAuthIdentifier() : ($r->ip() ?? 'unknown'),
        ));

        RateLimiter::for('login', fn (Request $r): Limit => Limit::perMinute(5)
            ->by($r->input('email') . '|' . ($r->ip() ?? 'unknown')));
    }
}
