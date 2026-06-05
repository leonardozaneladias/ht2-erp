<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('desloga um usuário desativado durante a sessão', function (): void {
    $u = criarAdminUser('u@teste.com');
    $this->actingAs($u, 'admin');
    $u->forceFill(['ativo' => false])->save();

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});

it('mantém um usuário ativo', function (): void {
    $u = criarAdminUser('u@teste.com');
    $this->actingAs($u, 'admin')->get(route('admin.dashboard'))->assertOk();
});
