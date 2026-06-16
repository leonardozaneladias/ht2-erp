<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        $middleware->appendToGroup('web', App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('api', [
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Requisições web sem autenticação válida (inclui o AuthenticateSession,
        // que desloga quando o hash da senha em sessão diverge) vão para o login
        // do admin — não para a rota 'login' padrão do framework (inexistente aqui).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->guest(route('admin.login'));
        });
    })->create();
