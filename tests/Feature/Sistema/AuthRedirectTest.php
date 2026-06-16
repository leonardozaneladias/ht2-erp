<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;

/**
 * O painel não tem rota chamada `login` (usa `admin.login`). Sem o tratamento
 * em bootstrap/app.php, uma AuthenticationException disparada fora do
 * AdminAuthenticate (ex.: AuthenticateSession deslogando por hash de senha
 * divergente) cairia no redirect padrão do framework para `route('login')` →
 * RouteNotFoundException (500). Estes testes travam o comportamento correto.
 */
it('redireciona AuthenticationException web para admin.login (não para a rota login)', function () {
    Route::middleware('web')->get('/_teste-auth-web', function (): void {
        throw new AuthenticationException;
    });

    $this->get('/_teste-auth-web')->assertRedirect(route('admin.login'));
});

it('mantém 401 para AuthenticationException em requisição JSON', function () {
    Route::middleware('web')->get('/_teste-auth-json', function (): void {
        throw new AuthenticationException;
    });

    $this->getJson('/_teste-auth-json')->assertStatus(401);
});
