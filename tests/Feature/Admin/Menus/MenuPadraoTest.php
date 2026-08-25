<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| A disposição padrão do menu deixou de ser código
|--------------------------------------------------------------------------
|
| Até aqui era a AplicarMenuPadraoAction: 60 linhas que gravavam 23 linhas em
| menu_personalizacoes no primeiro setup, hardcodando 'grupo-tab-rh',
| 'ref-cnaes', 'ref-cfops', 'ref-ncms', 'rh-departamentos' e 'rh-funcionarios'
| — o core conhecendo extensões pelo nome, que é exatamente o que ADR-0015
| proíbe na direção inversa.
|
| Ela fazia duas coisas: agrupar e ordenar. As duas viraram declaração na config
| de cada dono (`grupos`, `grupo`, `ordem`), então sobrou nada.
|
| O ganho colateral está no primeiro teste: a tabela nasce VAZIA. Antes, toda
| instalação nova chegava com 23 linhas que nenhum humano tinha escolhido, e a
| tela de Gestão de Menus marcava cada uma delas como "personalizado" — o badge
| não significava nada.
|
*/

it('a disposição padrão vem do config, com a tabela de personalizações vazia', function (): void {
    $secoes = app(MenuService::class)->estruturaParaSidebar(null, mostrarTudo: true);
    $administracao = collect($secoes)->firstWhere('key', 'administracao');
    $porKey = collect($administracao['items'])->keyBy('key');

    expect(MenuPersonalizacao::query()->count())->toBe(0)
        ->and(array_column($administracao['items'], 'key'))
        ->toBe(['grupo-cadastros', 'grupo-seguranca', 'auditoria', 'comunicados'])
        ->and(array_column($porKey['grupo-cadastros']['children'], 'key'))->toBe(['empresas', 'usuarios'])
        ->and(array_column($porKey['grupo-seguranca']['children'], 'key'))->toBe(['acesso', 'menus', 'configuracoes'])
        ->and($porKey['grupo-cadastros']['label'])->toBe('Organização')
        ->and($porKey['grupo-seguranca']['icon'])->toBe('tabler--shield-lock');

    // Principal intocada.
    $principal = collect($secoes)->firstWhere('key', 'principal');
    expect(array_column($principal['items'], 'key'))->toBe(['dashboard']);
});

it('a seção Tabelas Auxiliares agrupa os catálogos e o RH sem o core citar nenhum', function (): void {
    $secoes = app(MenuService::class)->estruturaParaSidebar(null, mostrarTudo: true);
    $porKey = collect(collect($secoes)->firstWhere('key', 'tabelas-auxiliares')['items'])->keyBy('key');

    expect(array_column($porKey['grupo-tab-cadastros']['children'], 'key'))->toBe([
        'ref-estados', 'ref-paises', 'ref-municipios', 'ref-moedas',
        'ref-bancos', 'ref-cargos', 'ref-tipos-logradouro',
        'ref-cnaes', 'ref-cfops', 'ref-ncms',
    ])->and(array_column($porKey['grupo-tab-rh']['children'], 'key'))
        ->toBe(['rh-departamentos', 'rh-funcionarios']);

    // Que o arranjo não veio do core é afirmado pelo guard A2, em
    // tests/Arch/CoreNaoConheceExtensaoTest.php — ali com varredura recursiva
    // de verdade e token_get_all, não com um glob de um nível só.
});

it('nada é marcado como personalizado numa instalação nova', function (): void {
    $gestao = app(MenuService::class)->estruturaParaGestao();

    $personalizados = collect($gestao['secoes'])
        ->flatMap(fn (array $secao): array => [$secao, ...$secao['items']])
        ->filter(fn (array $entrada): bool => (bool) ($entrada['personalizado'] ?? false))
        ->pluck('key')
        ->all();

    expect($personalizados)->toBe([])
        ->and($gestao['orfas'])->toBeEmpty();
});

it('concluir o Setup Wizard não grava personalização de menu nenhuma', function (): void {
    $this->seed(RolePermissionSeeder::class);
    marcarInstalado(false);

    Livewire\Livewire::test(HT2ML\Core\Livewire\Admin\Setup\SetupWizard::class)
        ->set('nome_cliente', 'Cliente Acme')
        ->call('proximo')
        ->set('nome_sistema', 'ERP Acme')
        ->call('proximo')
        ->set('admin_nome', 'Dono Acme')
        ->set('admin_email', 'dono@acme.com')
        ->set('admin_senha', 'SenhaForte1')
        ->call('concluir');

    expect(MenuPersonalizacao::query()->count())->toBe(0);

    // E a sidebar sai montada mesmo assim.
    $administracao = collect(app(MenuService::class)->estruturaParaSidebar(null, mostrarTudo: true))
        ->firstWhere('key', 'administracao');

    expect(array_column($administracao['items'], 'key'))->toContain('grupo-cadastros');
});
