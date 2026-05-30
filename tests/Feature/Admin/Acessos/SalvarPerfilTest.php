<?php

declare(strict_types=1);

use App\Livewire\Admin\Perfis\FormPerfil;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('cria perfil com nível, descrição e permissões', function () {
    $admin = criarAdminUser('admin@teste.com');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(FormPerfil::class)
        ->set('name', 'financeiro')
        ->set('nivel', 30)
        ->set('descricao', 'Equipe financeira')
        ->set('permissions', ['dashboard.view', 'usuarios.listar'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.perfis.index'));

    $role = Role::where('name', 'financeiro')->where('guard_name', 'admin')->firstOrFail();
    expect((int) $role->getAttribute('nivel'))->toBe(30);
    expect($role->load('permissions')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['dashboard.view', 'usuarios.listar']);
});

it('marcarModulo seleciona todas as permissões do módulo', function () {
    $admin = criarAdminUser('admin@teste.com');
    $admin->assignRole('super-admin');

    $component = Livewire::actingAs($admin, 'admin')
        ->test(FormPerfil::class)
        ->call('marcarModulo', 'usuarios');

    expect($component->get('permissions'))
        ->toContain('usuarios.listar', 'usuarios.criar', 'usuarios.editar', 'usuarios.deletar');
});

it('gestor não cria perfil de nível igual ou acima do seu', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail()->givePermissionTo('perfis.gerenciar');

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(FormPerfil::class)
        ->set('name', 'novo-alto')
        ->set('nivel', 60)
        ->call('salvar');

    expect(Role::where('name', 'novo-alto')->exists())->toBeFalse();
});
