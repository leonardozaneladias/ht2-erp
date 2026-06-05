<?php

declare(strict_types=1);

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('restaura o usuário original e limpa o contexto', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    Auth::guard('admin')->login($alvo);
    app(ImpersonationContext::class)->iniciar($original->id, 'suporte');

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('faz logout completo quando o original ficou inválido', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    Auth::guard('admin')->login($alvo);
    app(ImpersonationContext::class)->iniciar($original->id, 'suporte');
    $original->update(['ativo' => false]);

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('é no-op idempotente quando não há personificação', function (): void {
    $user = criarAdminUser('u@teste.com');
    Auth::guard('admin')->login($user);

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->id())->toBe($user->id);
});
