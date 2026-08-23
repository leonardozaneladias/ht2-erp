<?php

declare(strict_types=1);

use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('POST /admin/impersonation/sair restaura o usuário original', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->post(route('admin.impersonation.sair'))
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});
