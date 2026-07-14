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

it('expõe a busca da topbar com nome acessível (aria-label), não só placeholder', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $html = $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    // Placeholder não é nome acessível (some ao digitar; WCAG 3.3.2/4.1.2). O input de
    // busca precisa de aria-label próprio, consistente com x-admin.table.toolbar.
    expect($html)->toMatch('/id="topbar-search"[^>]*aria-label="Buscar\.\.\."/');
});

it('esconde do leitor de tela os ícones decorativos da topbar (aria-hidden)', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $html = $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    // Todos os botões da topbar já têm nome acessível (aria-label) ou texto visível,
    // então os ícones internos são puramente decorativos e devem sair da árvore de
    // acessibilidade (WCAG 1.1.1) — espelha button/input/kpi-card e demais da PEND-20.
    foreach (['menu-4', 'moon', 'sun', 'maximize', 'minimize', 'logout'] as $icone) {
        expect($html)->toMatch('/tabler--' . preg_quote($icone, '/') . '[^>]*aria-hidden="true"/');
    }

    // Ícone de busca: ancorado ao input da topbar (a classe colide com x-admin.table.toolbar).
    expect($html)->toMatch('/tabler--search[^>]*aria-hidden="true"\s*><\/i>\s*<input[^>]*id="topbar-search"/s');

    // Chevron do menu do usuário (classe distintiva da topbar).
    expect($html)->toMatch('/tabler--chevron-down hidden align-middle[^>]*aria-hidden="true"/');
});
