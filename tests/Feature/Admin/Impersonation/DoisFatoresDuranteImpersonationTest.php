<?php

declare(strict_types=1);

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('não força configurar 2FA do alvo enquanto personificando', function (): void {
    $settings = app(SegurancaSettings::class);
    $settings->exigir_2fa_admin = true;
    $settings->save();

    $original = criarAdminUser('original@teste.com'); // sem 2FA
    $alvo = criarAdminUser('alvo@teste.com');         // sem 2FA

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    // Sem o bypass, o middleware forçaria um redirect 302 para a tela de 2FA;
    // 200 prova que a personificação dispensou a exigência.
    $this->get(route('admin.dashboard'))->assertOk();
});
