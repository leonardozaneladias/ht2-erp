<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Menus\GestaoMenus;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    app(MenuService::class)->invalidarCache();
});

function permissaoAdmin(string $nome): Permission
{
    return Permission::query()->where('name', $nome)->where('guard_name', 'admin')->firstOrFail();
}

it('concede e revoga a permissão do item no perfil pelo toggle', function () {
    $gestor = Role::query()->where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();

    expect($gestor->hasPermissionTo('empresas.listar'))->toBeFalse();

    $componente = Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'empresas')
        ->call('alternarPerfil', $gestor->id)
        ->assertDispatched('toast', variant: 'success');

    expect($gestor->fresh()->hasPermissionTo('empresas.listar'))->toBeTrue();

    // O usuário com o perfil passa a enxergar o item (cache de acesso invalidado).
    $usuario = criarAdminUser('gestor@teste.com');
    $usuario->assignRole('gestor');

    expect($usuario->can('empresas.listar'))->toBeTrue();

    $componente->call('alternarPerfil', $gestor->id);

    expect($gestor->fresh()->hasPermissionTo('empresas.listar'))->toBeFalse();
});

it('bloqueia o toggle em role protegida (super-admin)', function () {
    $superAdmin = Role::query()->where('name', 'super-admin')->where('guard_name', 'admin')->firstOrFail();
    $antes = $superAdmin->permissions()->count();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'empresas')
        ->call('alternarPerfil', $superAdmin->id)
        ->assertDispatched('toast', variant: 'danger');

    expect($superAdmin->fresh()->permissions()->count())->toBe($antes);
});

it('bloqueia ator fora da hierarquia ou sem perfis.gerenciar', function () {
    $alvo = criarRoleAdmin('diretoria', 50);

    // Tem acesso à tela e gere perfis, mas o nível (10) não alcança o alvo (50).
    $semHierarquia = criarAdminUser('baixo@teste.com');
    $roleBaixa = criarRoleAdmin('operador-menus', 10);
    $roleBaixa->givePermissionTo(permissaoAdmin('configuracoes.menus'), permissaoAdmin('perfis.gerenciar'));
    $semHierarquia->assignRole($roleBaixa);

    Livewire::actingAs($semHierarquia, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'empresas')
        ->call('alternarPerfil', $alvo->id)
        ->assertDispatched('toast', variant: 'danger');

    expect($alvo->fresh()->hasPermissionTo('empresas.listar'))->toBeFalse();

    // Tem acesso à tela, mas não gere perfis (sem perfis.gerenciar).
    $semGestao = criarAdminUser('soumenus@teste.com');
    $roleMenus = criarRoleAdmin('so-menus', 90);
    $roleMenus->givePermissionTo(permissaoAdmin('configuracoes.menus'));
    $semGestao->assignRole($roleMenus);

    Livewire::actingAs($semGestao, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'empresas')
        ->call('alternarPerfil', $alvo->id)
        ->assertDispatched('toast', variant: 'danger');

    expect($alvo->fresh()->hasPermissionTo('empresas.listar'))->toBeFalse();
});

it('não expõe toggle para item sem permissão vinculada', function () {
    $gestor = Role::query()->where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();
    $antes = $gestor->permissions()->count();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'dashboard')
        ->assertSee('visível para todos os perfis')
        ->call('alternarPerfil', $gestor->id);

    // Sem permissão vinculada, o toggle é um no-op.
    expect($gestor->fresh()->permissions()->count())->toBe($antes);
});

it('registra a alternância na auditoria de acessos', function () {
    $gestor = Role::query()->where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();

    Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->call('editar', 'item', 'empresas')
        ->call('alternarPerfil', $gestor->id);

    $log = Activity::query()
        ->where('log_name', 'acessos')
        ->where('event', 'permissao_concedida')
        ->latest('id')
        ->firstOrFail();

    expect($log->causer_id)->toBe($this->admin->id)
        ->and($log->getProperty('permissao'))->toBe('empresas.listar')
        ->and($log->getProperty('origem'))->toBe('gestao-menus')
        ->and($log->subject_id)->toBe($gestor->id);
});
