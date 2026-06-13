<?php

declare(strict_types=1);

use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Database\Seeders\RolePermissionSeeder;
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

it('mantém o filtro de permissão por usuário', function () {
    $comum = criarAdminUser('comum@teste.com');

    $this->actingAs($comum, 'admin');

    $this->blade('<x-admin.sidebar />')
        ->assertSee('Dashboard')
        ->assertDontSee('Empresas');
});
