<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Acesso\SeletorPerfilAtivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->rh = Role::findOrCreate('rh', 'admin');
    $this->rh->givePermissionTo('usuarios.listar');
    $this->financeiro = Role::findOrCreate('financeiro', 'admin');
    $this->financeiro->givePermissionTo('configuracoes.editar');
});

it('define o perfil ativo na sessão', function () {
    $user = criarAdminUser();
    $user->assignRole(['rh', 'financeiro']);

    Livewire::actingAs($user, 'admin')
        ->test(SeletorPerfilAtivo::class)
        ->call('definir', $this->rh->id);

    expect(session('admin.perfil_ativo_role_id'))->toBe($this->rh->id);
    expect($user->fresh()->perfil_ativo_role_id)->toBe($this->rh->id);
});

it('limpa o perfil ativo (ver tudo)', function () {
    $user = criarAdminUser();
    $user->assignRole(['rh', 'financeiro']);
    session(['admin.perfil_ativo_role_id' => $this->rh->id]);

    Livewire::actingAs($user, 'admin')
        ->test(SeletorPerfilAtivo::class)
        ->call('definir', null);

    expect(session('admin.perfil_ativo_role_id'))->toBeNull();
});

it('o perfil ativo restringe as permissões efetivas via can()', function () {
    $user = criarAdminUser();
    $user->assignRole(['rh', 'financeiro']);
    $this->actingAs($user, 'admin');

    // Sem lente: soma das duas roles
    expect($user->can('usuarios.listar'))->toBeTrue();
    expect($user->can('configuracoes.editar'))->toBeTrue();

    Livewire::actingAs($user, 'admin')
        ->test(SeletorPerfilAtivo::class)
        ->call('definir', $this->rh->id);

    // Com lente "rh": só as permissões de rh contam
    expect($user->can('usuarios.listar'))->toBeTrue();
    expect($user->can('configuracoes.editar'))->toBeFalse();
});

it('não renderiza o seletor para usuário com uma só role', function () {
    $user = criarAdminUser();
    $user->assignRole('rh');

    Livewire::actingAs($user, 'admin')
        ->test(SeletorPerfilAtivo::class)
        ->assertDontSee('Atuar como');
});
