<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\SegurancaConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Coloca o usuário logado "dentro" de uma personificação ativa.
 *
 * Em testes de Livewire não usamos $this->withSession(), pois ele só é aplicado em
 * requests HTTP reais (get/post); aqui escrevemos direto na sessão viva, que é a
 * mesma lida por ImpersonationContext dentro do Livewire::test().
 */
function personificando(string $original = 'original@teste.com'): void
{
    $atorOriginal = criarAdminUser($original);

    session([
        'impersonate.original_id' => $atorOriginal->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
        // Já confirmado recentemente: isola o teste na trava de personificação,
        // não na reconfirmação de senha.
        'auth.password_confirmed_at' => time(),
    ]);
}

/*
 * A trava lança AuthorizationException, que o harness de teste do Livewire
 * (RequestBroker) converte em resposta 403 em vez de propagar — por isso
 * asseguramos com assertForbidden(), não com ->throws().
 */

it('bloqueia ativar 2FA enquanto personificando', function (): void {
    $alvo = criarAdminUser('alvo@teste.com');
    $this->actingAs($alvo, 'admin');
    personificando();

    Livewire::test(SegurancaConta::class)->call('ativar')->assertForbidden();
});

it('bloqueia confirmar 2FA enquanto personificando', function (): void {
    $alvo = criarAdminUser('alvo@teste.com');
    $this->actingAs($alvo, 'admin');
    personificando();

    Livewire::test(SegurancaConta::class)->call('confirmar')->assertForbidden();
});

it('bloqueia regenerar códigos enquanto personificando', function (): void {
    $alvo = criarAdminUser('alvo@teste.com');
    $this->actingAs($alvo, 'admin');
    personificando();

    Livewire::test(SegurancaConta::class)->call('regenerar')->assertForbidden();
});

it('bloqueia desativar 2FA enquanto personificando', function (): void {
    $alvo = criarAdminUser('alvo@teste.com');
    $this->actingAs($alvo, 'admin');
    personificando();

    Livewire::test(SegurancaConta::class)->call('desativar')->assertForbidden();
});
