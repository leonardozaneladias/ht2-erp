<?php

declare(strict_types=1);

use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Access\AuthorizationException;

it('guarda, expõe, expira e limpa o estado da personificação', function (): void {
    $ctx = app(ImpersonationContext::class);

    expect($ctx->ativo())->toBeFalse();

    $ctx->iniciar(7, 'suporte ao cliente');

    expect($ctx->ativo())->toBeTrue()
        ->and($ctx->originalId())->toBe(7)
        ->and($ctx->motivo())->toBe('suporte ao cliente')
        ->and($ctx->expirado(30))->toBeFalse();

    $ctx->encerrar();

    expect($ctx->ativo())->toBeFalse()
        ->and($ctx->originalId())->toBeNull();
});

it('garantirNaoPersonificando lança quando ativo e é no-op quando inativo', function (): void {
    $ctx = app(ImpersonationContext::class);

    $ctx->garantirNaoPersonificando(); // não lança

    $ctx->iniciar(1, 'motivo qualquer');

    expect(fn () => $ctx->garantirNaoPersonificando())->toThrow(AuthorizationException::class);
});
