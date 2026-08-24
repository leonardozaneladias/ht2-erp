<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
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

it('renomeia um item e troca o ícone pelo drawer', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'usuarios')
        ->assertSet('label', 'Usuários admin')
        ->assertSet('icone', 'tabler--users')
        ->assertDispatched('menus-abrir-editor')
        ->set('label', 'Equipe')
        ->set('icone', 'tabler--users-group')
        ->call('salvarEdicao')
        ->assertHasNoErrors()
        ->assertDispatched('menus-fechar-editor');

    $personalizacao = MenuPersonalizacao::query()->where('key', 'usuarios')->firstOrFail();

    expect($personalizacao->label)->toBe('Equipe')
        ->and($personalizacao->icone)->toBe('tabler--users-group');

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $item = collect($secoes)->flatMap(fn (array $secao): array => $secao['items'])->firstWhere('key', 'usuarios');

    expect($item['label'])->toBe('Equipe')
        ->and($item['icon'])->toBe('tabler--users-group');
});

it('renomeia uma seção pelo drawer', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'secao', 'administracao')
        ->assertSet('label', 'Administração')
        ->set('label', 'Gestão do Sistema')
        ->call('salvarEdicao')
        ->assertHasNoErrors();

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);

    expect(collect($secoes)->firstWhere('key', 'administracao')['title'])->toBe('Gestão do Sistema');
});

it('rejeita ícone fora da lista curada', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'usuarios')
        ->set('icone', 'tabler--icone-que-nao-existe')
        ->call('salvarEdicao')
        ->assertHasErrors(['icone']);

    expect(MenuPersonalizacao::query()->where('key', 'usuarios')->exists())->toBeFalse();
});

it('alterna o ativo do item preservando as demais personalizações', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'comunicados', 'label' => 'Avisos', 'ordem' => 3]);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('alternarAtivo', 'comunicados')
        ->assertDispatched('toast', variant: 'success');

    $personalizacao = MenuPersonalizacao::query()->where('key', 'comunicados')->firstOrFail();

    expect($personalizacao->ativo)->toBeFalse()
        ->and($personalizacao->label)->toBe('Avisos')
        ->and($personalizacao->ordem)->toBe(3);

    // Reativa.
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('alternarAtivo', 'comunicados');

    expect($personalizacao->fresh()->ativo)->toBeTrue();
});

it('remove a linha quando a edição volta tudo ao padrão', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'usuarios')
        ->assertSet('label', 'Equipe')
        ->set('label', 'Usuários admin')
        ->call('salvarEdicao')
        ->assertHasNoErrors();

    expect(MenuPersonalizacao::query()->where('key', 'usuarios')->exists())->toBeFalse();
});

it('restaura um registro específico pelo evento de confirmação', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe', 'ordem' => 1]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'label' => 'Organizações']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->dispatch('menus::restaurar-registro', tipo: 'item', key: 'usuarios')
        ->assertDispatched('toast', variant: 'success');

    expect(MenuPersonalizacao::query()->where('key', 'usuarios')->exists())->toBeFalse()
        ->and(MenuPersonalizacao::query()->where('key', 'empresas')->exists())->toBeTrue();
});
