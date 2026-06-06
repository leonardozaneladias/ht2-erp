<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('a topbar aponta os itens de perfil/conta para admin.conta', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $resposta = $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    $resposta->assertSee(route('admin.conta'), false);
});

it('renderiza o avatar com iniciais quando não há foto', function (): void {
    $user = criarAdminUser('chrome@teste.com'); // nome "Usuário Teste"

    $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('UT'); // iniciais de "Usuário Teste"
});

it('concentra as ações de conta no topbar, sem duplicar na sidebar', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $resposta = $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    // O menu de ações (incluindo logout) vive só no topbar; a sidebar é só identidade.
    expect(substr_count($resposta->getContent(), route('admin.logout')))->toBe(1);

    // O dropdown de ações do topbar permanece (textos exclusivos dele).
    $resposta->assertSee('Bem-vindo de volta');
    $resposta->assertSee('Configurações da conta');
});
