<?php

declare(strict_types=1);

use HT2ML\Core\Settings\SegurancaSettings;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('reverte ao original quando a personificação passa do teto de tempo', function (): void {
    $settings = app(SegurancaSettings::class);
    $settings->impersonation_timeout_minutos = 30;
    $settings->save();

    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => Carbon::now()->subMinutes(31)->timestamp,
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))->assertOk();

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('mantém a personificação dentro do teto', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => Carbon::now()->subMinutes(5)->timestamp,
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))->assertOk();

    expect(Auth::guard('admin')->id())->toBe($alvo->id);
});
