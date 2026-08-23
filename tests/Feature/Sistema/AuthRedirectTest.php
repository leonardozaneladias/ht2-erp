<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

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

/*
 * Cenário real (não coberto pelos testes acima): o AuthenticateSession desloga
 * quando o hash da senha em sessão diverge (troca de senha / migrate:fresh
 * recriando o usuário). Seu redirectTo() executa o callback do framework, que
 * por padrão aponta para route('login') — inexistente aqui. Como a
 * RouteNotFoundException estoura DENTRO do redirectTo (antes da
 * AuthenticationException), o handler de bootstrap/app.php não a captura → 500.
 * O fix é configurar redirectGuestsTo(route('admin.login')) no withMiddleware.
 */
it('desloga via AuthenticateSession (hash divergente) sem estourar RouteNotFound', function () {
    $admin = AdminUser::factory()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['password_hash_admin' => 'hash-de-senha-obsoleto'])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});
