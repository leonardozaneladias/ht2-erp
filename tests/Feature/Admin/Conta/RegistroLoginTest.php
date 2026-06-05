<?php

declare(strict_types=1);

use App\Models\LoginHistory;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registra o login do guard admin e atualiza last_login', function (): void {
    $user = criarAdminUser('login@teste.com');

    event(new Login('admin', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(1)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('não registra durante personificação', function (): void {
    $user = criarAdminUser('login@teste.com');
    app(ImpersonationContext::class)->iniciar(999, 'suporte');

    event(new Login('admin', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(0);
});

it('ignora guards que não sejam admin', function (): void {
    $user = criarAdminUser('login@teste.com');

    event(new Login('web', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(0);
});
