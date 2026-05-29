<?php

declare(strict_types=1);

use App\Livewire\Admin\Perfis\FormPerfil;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('cria novo perfil com permissões e grava activity log', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormPerfil::class)
        ->set('name', 'editor')
        ->set('permissions', ['dashboard.view', 'usuarios.listar'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.perfis.index'));

    $role = Role::where('name', 'editor')->where('guard_name', 'admin')->firstOrFail();
    expect($role->permissions->pluck('name')->all())->toEqualCanonicalizing([
        'dashboard.view',
        'usuarios.listar',
    ]);
});

it('valida nome único do perfil no guard admin', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormPerfil::class)
        ->set('name', 'super-admin')
        ->call('salvar')
        ->assertHasErrors(['name']);
});
