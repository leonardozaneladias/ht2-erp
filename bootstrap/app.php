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
        then: static function (): void {},
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // O painel usa a rota nomeada 'admin.login' (não há 'login'). Sobrescreve o
        // default do framework (route('login')) para Authenticate E AuthenticateSession
        // — este desloga por hash de senha divergente e resolvia o redirect via callback
        // estático, estourando RouteNotFoundException antes do handler de exceções.
        $middleware->redirectGuestsTo(fn (): string => route('admin.login'));

        $middleware->statefulApi();
        $middleware->encryptCookies(except: ['appearance', 'admin-mode']);
        $middleware->alias([
            'admin.auth' => HT2ML\Core\Http\Middleware\AdminAuthenticate::class,
        ]);
        $middleware->append(HT2ML\Core\Http\Middleware\AttachRequestId::class);
        $middleware->appendToGroup('web', HT2ML\Core\Http\Middleware\SecurityHeaders::class);
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
