<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    app(MenuService::class)->invalidarCache();
});

it('renderiza a sidebar com personalizações aplicadas', function () {
    MenuPersonalizacao::create([
        'tipo' => 'item',
        'key' => 'usuarios',
        'label' => 'Equipe',
        'icone' => 'tabler--user-star',
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'comunicados', 'ativo' => false]);

    $this->actingAs($this->admin, 'admin');

    $this->blade('<x-admin.sidebar />')
        ->assertSee('Equipe')
        ->assertSee('tabler--user-star')
        ->assertDontSee('Usuários admin')
        ->assertDontSee('Comunicados');
});

it('aplica a ordem personalizada na sidebar', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'configuracoes', 'ordem' => 1]);

    $this->actingAs($this->admin, 'admin');

    $html = (string) $this->blade('<x-admin.sidebar />');

    expect(strpos($html, 'Configurações'))->toBeLessThan(strpos($html, 'Empresas'));
});

it('não quebra com personalização órfã e a omite do menu', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'modulo-antigo', 'label' => 'Fantasma']);

    $this->actingAs($this->admin, 'admin');

    $this->blade('<x-admin.sidebar />')
        ->assertSee('Dashboard')
        ->assertDontSee('Fantasma');
});

it('renderiza grupo como accordion com filho ativo destacado', function () {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-pessoas', 'label' => 'Pessoas',
        'icone' => 'tabler--users-group', 'secao_key' => 'administracao', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas']);

    // Página real: a rota ativa (usuários) está dentro do grupo → accordion aberto.
    $resposta = $this->actingAs($this->admin, 'admin')->get(route('admin.usuarios.index'));

    $resposta->assertOk()
        ->assertSee('Pessoas')
        ->assertSee('menu-grupo-pessoas')
        ->assertSee('aria-expanded="true"', escape: false)
        ->assertSee('hs-accordion');
});

it('marca o item de menu de topo ativo com aria-current="page"', function () {
    // Página real: a rota ativa (usuários) torna o item de topo correspondente ativo.
    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.usuarios.index'))
        ->assertOk()
        ->getContent();

    // O link ativo (menu-link active) anuncia a página atual a leitores de tela.
    // O padrão `menu-link active"` distingue o link da sidebar do aria-current do breadcrumb.
    expect($html)->toMatch('/menu-link active"\s+aria-current="page"/');
});

it('marca o filho ativo com aria-current="page" sem marcar o toggle do grupo', function () {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-pessoas', 'label' => 'Pessoas',
        'icone' => 'tabler--users-group', 'secao_key' => 'administracao', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas']);

    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.usuarios.index'))
        ->assertOk()
        ->getContent();

    // O link filho ativo recebe aria-current="page"...
    expect($html)->toMatch('/menu-link active"\s+aria-current="page"/');
    // ...mas o toggle do grupo (href javascript:void(0), não navega) NÃO recebe:
    // seu próximo atributo após a classe é aria-controls, nunca aria-current.
    expect($html)->toMatch('/hs-accordion-toggle menu-link"\s+aria-controls/');
});

it('expõe o menu principal como landmark de navegação nomeado', function () {
    $this->actingAs($this->admin, 'admin');

    $html = (string) $this->blade('<x-admin.sidebar />');

    // O container do menu principal deve ser um landmark `navigation` nomeado, para que
    // leitores de tela permitam pular direto para a navegação (WAI-ARIA Landmarks).
    expect($html)->toMatch('/id="sidenav-menu"\s+role="navigation"\s+aria-label="Menu principal"/');
});

it('esconde do leitor de tela os ícones decorativos dos itens de menu (aria-hidden)', function () {
    $this->actingAs($this->admin, 'admin');

    $html = (string) $this->blade('<x-admin.sidebar />');

    // Cada link de menu já é nomeado pelo texto (`menu-text`), então o ícone do item é
    // puramente decorativo e deve sair da árvore de acessibilidade (WCAG 1.1.1) — espelha
    // topbar/button/input e demais da PEND-20. O \s* tolera as quebras de linha que o
    // Prettier insere entre as tags (formatação não é contrato).
    expect($html)->toMatch('/class="menu-icon"\s*><i class="iconify [^"]+" aria-hidden="true"><\/i\s*>/');

    // Cobertura total: nenhum ícone de menu pode permanecer sem aria-hidden (cobre tanto o
    // ícone de grupo/accordion quanto o de item de topo, que usam o mesmo markup).
    expect($html)->not->toMatch('/class="menu-icon"\s*><i class="iconify [^"]+"><\/i\s*>/');
});

it('mantém o filtro de permissão por usuário', function () {
    $comum = criarAdminUser('comum@teste.com');

    $this->actingAs($comum, 'admin');

    $this->blade('<x-admin.sidebar />')
        ->assertSee('Dashboard')
        ->assertDontSee('Empresas');
});
