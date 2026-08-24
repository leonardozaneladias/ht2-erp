<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\Menu\CriarGrupoMenuAction;
use HT2ML\Core\Actions\Admin\Menu\CriarSecaoMenuAction;
use HT2ML\Core\Actions\Admin\Menu\ExcluirPersonalizacaoCustomAction;
use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Livewire\Admin\Menus\GestaoMenus;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    app(MenuService::class)->invalidarCache();
});

function criarGrupoDeTeste(string $key = 'grupo-pessoas', string $secao = 'administracao'): MenuPersonalizacao
{
    $grupo = MenuPersonalizacao::create([
        'tipo' => 'grupo',
        'key' => $key,
        'label' => 'Pessoas',
        'icone' => 'tabler--users-group',
        'secao_key' => $secao,
        'e_custom' => true,
    ]);

    app(MenuService::class)->invalidarCache();

    return $grupo;
}

it('move item para dentro do grupo gravando grupo_key e limpando secao_key', function () {
    criarGrupoDeTeste();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'grupo:grupo-pessoas', [
            'grupo:grupo-pessoas' => ['usuarios'],
            'secao:administracao' => ['empresas', 'acesso', 'auditoria', 'comunicados', 'configuracoes', 'menus', 'grupo-pessoas'],
        ])
        ->assertHasNoErrors();

    $usuarios = MenuPersonalizacao::query()
        ->where('tipo', TipoPersonalizacaoMenu::Item)
        ->where('key', 'usuarios')
        ->firstOrFail();

    expect($usuarios->grupo_key)->toBe('grupo-pessoas')
        ->and($usuarios->secao_key)->toBeNull();

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $grupo = collect($secoes)->flatMap(fn (array $secao): array => $secao['items'])->firstWhere('key', 'grupo-pessoas');

    expect(array_column($grupo['children'], 'key'))->toBe(['usuarios']);
});

it('devolve item do grupo para a raiz limpando grupo_key', function () {
    criarGrupoDeTeste();
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'secao:administracao', [
            'secao:administracao' => ['usuarios', 'empresas', 'acesso', 'auditoria', 'comunicados', 'configuracoes', 'menus', 'grupo-pessoas'],
        ])
        ->assertHasNoErrors();

    $usuarios = MenuPersonalizacao::query()
        ->where('tipo', TipoPersonalizacaoMenu::Item)
        ->where('key', 'usuarios')
        ->firstOrFail();

    expect($usuarios->grupo_key)->toBeNull()
        ->and($usuarios->secao_key)->toBeNull()
        ->and($usuarios->ordem)->toBe(1);
});

it('reordena e move o próprio grupo entre seções pelo payload raiz', function () {
    criarGrupoDeTeste();
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'grupo-pessoas', 'secao:principal', [
            'secao:principal' => ['dashboard', 'grupo-pessoas'],
            'secao:administracao' => ['empresas', 'acesso', 'auditoria', 'comunicados', 'configuracoes', 'menus'],
        ])
        ->assertHasNoErrors();

    $grupo = MenuPersonalizacao::query()->where('key', 'grupo-pessoas')->firstOrFail();

    expect($grupo->secao_key)->toBe('principal')
        ->and($grupo->ordem)->toBe(2);

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $principal = collect($secoes)->firstWhere('key', 'principal');

    expect(array_column($principal['items'], 'key'))->toBe(['dashboard', 'grupo-pessoas']);
});

it('rejeita grupo dentro de grupo e containers desconhecidos', function () {
    criarGrupoDeTeste('grupo-a');
    criarGrupoDeTeste('grupo-b');

    // Grupo dentro de grupo.
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'grupo-b', 'grupo:grupo-a', ['grupo:grupo-a' => ['grupo-b']])
        ->assertDispatched('toast', variant: 'danger');

    // Container de grupo inexistente.
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'grupo:fantasma', ['grupo:fantasma' => ['usuarios']])
        ->assertDispatched('toast', variant: 'danger');

    // Container sem prefixo (formato antigo) é rejeitado.
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'administracao', ['administracao' => ['usuarios']])
        ->assertDispatched('toast', variant: 'danger');

    expect(MenuPersonalizacao::query()->where('tipo', TipoPersonalizacaoMenu::Item)->count())->toBe(0);
});

it('cria seção custom com key única e a reordena', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->set('novaSecaoLabel', 'Operações')
        ->call('criarSecao')
        ->assertHasNoErrors()
        ->assertDispatched('toast', variant: 'success');

    $secao = MenuPersonalizacao::query()->where('tipo', TipoPersonalizacaoMenu::Secao)->firstOrFail();

    expect($secao->key)->toBe('secao-operacoes')
        ->and($secao->label)->toBe('Operações')
        ->and($secao->e_custom)->toBeTrue();

    // Colisão de slug ganha sufixo.
    $segunda = app(CriarSecaoMenuAction::class)->execute('Operações');
    expect($segunda->key)->toBe('secao-operacoes-2');

    // Reordenável junto com as seções do config.
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarSecoes', ['secao-operacoes', 'principal', 'administracao', 'secao-operacoes-2'])
        ->assertHasNoErrors();

    $secoes = app(MenuService::class)->estruturaParaGestao()['secoes'];
    expect(array_column($secoes, 'key'))->toBe(['secao-operacoes', 'principal', 'administracao', 'secao-operacoes-2', 'negocio', 'tabelas-auxiliares']);
});

it('cria grupo validando ícone, seção e colisão com keys de itens', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->set('novoGrupoLabel', 'Relatórios')
        ->set('novoGrupoIcone', 'tabler--chart-bar')
        ->call('criarGrupo', 'administracao')
        ->assertHasNoErrors();

    $grupo = MenuPersonalizacao::query()->where('tipo', TipoPersonalizacaoMenu::Grupo)->firstOrFail();

    expect($grupo->key)->toBe('grupo-relatorios')
        ->and($grupo->icone)->toBe('tabler--chart-bar')
        ->and($grupo->secao_key)->toBe('administracao');

    // Ícone fora da lista curada.
    expect(fn () => app(CriarGrupoMenuAction::class)->execute('X', 'tabler--nao-existe', 'administracao'))
        ->toThrow(InvalidArgumentException::class);

    // Seção inexistente.
    expect(fn () => app(CriarGrupoMenuAction::class)->execute('X', 'tabler--folder', 'nada'))
        ->toThrow(InvalidArgumentException::class);

    // Slug que colide com key de ITEM do config ganha sufixo (resolver da
    // reordenação distingue grupo × item pela key).
    config(['admin-menu' => array_merge(config('admin-menu'), [[
        'key' => 'extra',
        'title' => 'Extra',
        'items' => [['key' => 'grupo-vendas', 'label' => 'Vendas', 'icon' => 'tabler--tag', 'route' => 'admin.dashboard']],
    ]])]);
    app(MenuService::class)->invalidarCache();

    $colidente = app(CriarGrupoMenuAction::class)->execute('Vendas', 'tabler--tag', 'extra');
    expect($colidente->key)->toBe('grupo-vendas-2');
});

it('exclui grupo custom devolvendo os filhos à posição natural', function () {
    criarGrupoDeTeste();
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->dispatch('menus::excluir-custom', tipo: 'grupo', key: 'grupo-pessoas')
        ->assertDispatched('toast', variant: 'success');

    expect(MenuPersonalizacao::query()->where('tipo', TipoPersonalizacaoMenu::Grupo)->count())->toBe(0);

    // O filho volta à seção natural (grupo_key vestigial cai no fallback).
    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $administracao = collect($secoes)->firstWhere('key', 'administracao');

    expect(array_column($administracao['items'], 'key'))->toContain('usuarios');
});

it('recusa excluir registros que não são custom', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe']);

    expect(fn () => app(ExcluirPersonalizacaoCustomAction::class)->execute(TipoPersonalizacaoMenu::Item, 'usuarios'))
        ->toThrow(InvalidArgumentException::class);

    expect(MenuPersonalizacao::query()->where('key', 'usuarios')->exists())->toBeTrue();
});

it('edita grupo pelo drawer exigindo nome e mantém a edição sem virar delete', function () {
    criarGrupoDeTeste();

    $componente = Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'grupo', 'grupo-pessoas')
        ->assertSet('label', 'Pessoas')
        ->assertSet('icone', 'tabler--users-group')
        ->assertSet('editandoEhCustom', true);

    // Nome obrigatório para custom.
    $componente->set('label', '')->call('salvarEdicao')->assertHasErrors(['label']);

    $componente
        ->set('label', 'Time')
        ->set('icone', 'tabler--id-badge')
        ->call('salvarEdicao')
        ->assertHasNoErrors();

    $grupo = MenuPersonalizacao::query()->where('key', 'grupo-pessoas')->firstOrFail();

    expect($grupo->label)->toBe('Time')
        ->and($grupo->icone)->toBe('tabler--id-badge');
});

it('alterna o ativo do grupo e restaurar tudo apaga os customs', function () {
    criarGrupoDeTeste();
    MenuPersonalizacao::create(['tipo' => 'secao', 'key' => 'secao-extra', 'label' => 'Extra', 'e_custom' => true]);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('alternarAtivoGrupo', 'grupo-pessoas')
        ->assertDispatched('toast', variant: 'success');

    expect(MenuPersonalizacao::query()->where('key', 'grupo-pessoas')->value('ativo'))->toBeFalse();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->dispatch('menus::restaurar-tudo');

    expect(MenuPersonalizacao::query()->count())->toBe(0);
});

it('não trata customs como órfãs ao limpar órfãs', function () {
    criarGrupoDeTeste();
    MenuPersonalizacao::create(['tipo' => 'secao', 'key' => 'secao-extra', 'label' => 'Extra', 'e_custom' => true]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'item-removido', 'label' => 'Órfã']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->dispatch('menus::limpar-orfas');

    expect(MenuPersonalizacao::query()->where('key', 'item-removido')->exists())->toBeFalse()
        ->and(MenuPersonalizacao::query()->where('key', 'grupo-pessoas')->exists())->toBeTrue()
        ->and(MenuPersonalizacao::query()->where('key', 'secao-extra')->exists())->toBeTrue();
});
