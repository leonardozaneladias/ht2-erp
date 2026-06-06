<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplica o locale do usuário na request autenticada', function (): void {
    $user = criarAdminUser('loc@teste.com');
    $user->forceFill(['locale' => 'en'])->save();

    $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('mantém o locale padrão quando o usuário não definiu', function (): void {
    $user = criarAdminUser('loc@teste.com');

    $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    expect(app()->getLocale())->toBe(config('app.locale'));
});
