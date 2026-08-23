<?php

declare(strict_types=1);

use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logout durante a personificação encerra tudo e audita', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'impersonation')->where('event', 'encerrada')->exists())
        ->toBeTrue();
});
