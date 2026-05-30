<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('gestor não pode editar usuário de nível igual', function () {
    Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail()->givePermissionTo('usuarios.editar');

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');
    $outro = criarAdminUser('og@teste.com');
    $outro->assignRole('gestor');

    expect($gestor->can('update', $outro))->toBeFalse();
});

it('gestor pode editar usuário de nível inferior', function () {
    Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail()->givePermissionTo('usuarios.editar');
    $analista = Role::findOrCreate('analista', 'admin');
    DB::table('roles')->where('id', $analista->id)->update(['nivel' => 10]);

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');
    $alvo = criarAdminUser('a@teste.com');
    $alvo->assignRole('analista');

    expect($gestor->can('update', $alvo))->toBeTrue();
});

it('super-admin edita qualquer usuário', function () {
    $super = criarAdminUser('s@teste.com');
    $super->assignRole('super-admin');
    $alvo = criarAdminUser('a@teste.com');
    $alvo->assignRole('gestor');

    expect($super->can('update', $alvo))->toBeTrue();
});

it('a role protegida não pode ser alterada/excluída via policy por não-super-admin', function () {
    Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail()->givePermissionTo('perfis.gerenciar');
    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');
    $superRole = Role::where('name', 'super-admin')->where('guard_name', 'admin')->firstOrFail();

    expect($gestor->can('update', $superRole))->toBeFalse();
    expect($gestor->can('delete', $superRole))->toBeFalse();
});
