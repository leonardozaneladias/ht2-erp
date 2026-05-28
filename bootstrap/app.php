<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: static function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->encryptCookies(except: ['appearance', 'admin-mode']);
        $middleware->alias([
            'admin.auth' => App\Http\Middleware\AdminAuthenticate::class,
        ]);
        $middleware->append(App\Http\Middleware\AttachRequestId::class);
        $middleware->appendToGroup('api', [
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
