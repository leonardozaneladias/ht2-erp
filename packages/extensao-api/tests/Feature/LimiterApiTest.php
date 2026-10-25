<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fixtures/UsuarioDeApi.php';

use HT2ML\Api\ApiServiceProvider;
use HT2ML\Api\Tests\Fixtures\UsuarioDeApi;
use Illuminate\Http\Request;

it('a chave do limiter separa usuários de tipos diferentes com o mesmo id', function (): void {
    $doApi = Request::create('/');
    $doApi->setUserResolver(fn () => UsuarioDeApi::com(7));

    $doPainel = Request::create('/');
    // Outro modelo autenticável qualquer, com o mesmo id.
    $doPainel->setUserResolver(fn () => (new class extends Illuminate\Foundation\Auth\User {})->forceFill(['id' => 7]));

    expect(ApiServiceProvider::chaveDoChamador($doApi))
        ->not->toBe(ApiServiceProvider::chaveDoChamador($doPainel))
        ->and(ApiServiceProvider::chaveDoChamador($doApi))->toEndWith(':7');
});

it('sem usuário, a chave é o IP', function (): void {
    expect(ApiServiceProvider::chaveDoChamador(Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.9'])))
        ->toBe('ip:10.0.0.9');
});
