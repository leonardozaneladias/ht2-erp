<?php

declare(strict_types=1);

use App\Livewire\Admin\Menus\GestaoMenus;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    app(MenuService::class)->invalidarCache();
});

it('exige a permissão configuracoes.menus', function () {
    $semAcesso = criarAdminUser('comum@teste.com');

    Livewire::actingAs($semAcesso, 'admin')
        ->test(GestaoMenus::class)
        ->assertForbidden();

    $gestorMenus = criarAdminUser('menus@teste.com');
    $role = criarRoleAdmin('gestor-menus', 10);
    $role->givePermissionTo(
        Permission::query()->where('name', 'configuracoes.menus')->where('guard_name', 'admin')->firstOrFail(),
    );
    $gestorMenus->assignRole($role);

    Livewire::actingAs($gestorMenus, 'admin')
        ->test(GestaoMenus::class)
        ->assertOk()
        ->assertSee('Gestão de menus');
});

it('abre a página pela rota como super-admin', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertSee('Gestão de menus');
});

it('reordena itens dentro da própria seção sem gravar secao_key', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'secao:administracao', [
            'secao:administracao' => ['usuarios', 'empresas', 'acesso', 'auditoria', 'comunicados', 'configuracoes', 'menus'],
        ])
        ->assertHasNoErrors()
        ->assertDispatched('toast', variant: 'success');

    $usuarios = MenuPersonalizacao::query()->where('key', 'usuarios')->firstOrFail();

    expect($usuarios->ordem)->toBe(1)
        ->and($usuarios->secao_key)->toBeNull();

    // O cache foi invalidado: a sidebar reflete a nova ordem.
    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $administracao = collect($secoes)->firstWhere('key', 'administracao');

    expect(array_column($administracao['items'], 'key')[0])->toBe('usuarios');
});

it('move item para outra seção gravando secao_key', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'empresas', 'secao:principal', [
            'secao:principal' => ['dashboard', 'empresas'],
            'secao:administracao' => ['acesso', 'usuarios', 'auditoria', 'comunicados', 'configuracoes', 'menus'],
        ])
        ->assertHasNoErrors();

    $empresas = MenuPersonalizacao::query()->where('key', 'empresas')->firstOrFail();

    expect($empresas->secao_key)->toBe('principal')
        ->and($empresas->ordem)->toBe(2);

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);
    $principal = collect($secoes)->firstWhere('key', 'principal');

    expect(array_column($principal['items'], 'key'))->toBe(['dashboard', 'empresas']);
});

it('reordena seções', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarSecoes', ['administracao', 'principal'])
        ->assertHasNoErrors();

    $secoes = app(MenuService::class)->estruturaParaSidebar($this->admin);

    expect(array_column($secoes, 'key'))->toBe(['administracao', 'principal', 'negocio']);
});

it('rejeita payload com keys desconhecidas sem persistir nada', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'item-hacker', 'secao:administracao', ['secao:administracao' => ['usuarios']])
        ->assertDispatched('toast', variant: 'danger');

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarSecoes', ['secao-hacker'])
        ->assertDispatched('toast', variant: 'danger');

    expect(MenuPersonalizacao::query()->count())->toBe(0);
});

it('restaura o menu padrão removendo todas as personalizações', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe', 'ordem' => 1]);
    MenuPersonalizacao::create(['tipo' => 'secao', 'key' => 'administracao', 'ordem' => 1]);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->dispatch('menus::restaurar-tudo')
        ->assertDispatched('toast', variant: 'success');

    expect(MenuPersonalizacao::query()->count())->toBe(0);
});

it('limpa apenas as personalizações órfãs', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'modulo-removido', 'label' => 'Fantasma']);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe']);
    app(MenuService::class)->invalidarCache();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->assertSee('Personalizações órfãs')
        ->dispatch('menus::limpar-orfas')
        ->assertDispatched('toast', variant: 'success');

    expect(MenuPersonalizacao::query()->where('key', 'modulo-removido')->exists())->toBeFalse()
        ->and(MenuPersonalizacao::query()->where('key', 'usuarios')->exists())->toBeTrue();
});

it('registra o resumo de domínio da reordenação na auditoria', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('reordenarItens', 'usuarios', 'secao:administracao', [
            'secao:administracao' => ['usuarios', 'empresas', 'acesso', 'auditoria', 'comunicados', 'configuracoes', 'menus'],
        ]);

    $log = App\Models\Activity::query()
        ->where('log_name', 'menus')
        ->where('event', 'menu_reordenado')
        ->latest('id')
        ->firstOrFail();

    expect($log->causer_id)->toBe($this->admin->id)
        ->and($log->getProperty('item'))->toBe('usuarios');
});
