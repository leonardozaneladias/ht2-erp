<?php

declare(strict_types=1);

use App\Actions\Admin\SyncPermissoesPerfilAction;
use App\Actions\Admin\SyncRolesUsuarioAction;
use App\DTOs\Admin\SyncPermissoesPerfilDTO;
use App\DTOs\Admin\SyncRolesDTO;
use App\Exceptions\AccessException;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('sincroniza as roles do usuário', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    $resultado = app(SyncRolesUsuarioAction::class)->execute(
        SyncRolesDTO::fromArray(['adminUserId' => $user->id, 'roles' => ['gestor']]),
    );

    expect($resultado->getRoleNames()->all())->toBe(['gestor']);
});

it('bloqueia remover a role do último super-admin via sync', function () {
    $super = criarAdminUser('s@teste.com');
    $super->assignRole('super-admin');

    expect(fn () => app(SyncRolesUsuarioAction::class)->execute(
        SyncRolesDTO::fromArray(['adminUserId' => $super->id, 'roles' => ['gestor']]),
    ))->toThrow(AccessException::class);
});

it('sincroniza as permissões de um perfil', function () {
    $role = Role::findOrCreate('editor', 'admin');

    $resultado = app(SyncPermissoesPerfilAction::class)->execute(
        SyncPermissoesPerfilDTO::fromArray([
            'roleId' => $role->id,
            'permissoes' => ['dashboard.view', 'usuarios.listar'],
        ]),
    );

    expect($resultado->load('permissions')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['dashboard.view', 'usuarios.listar']);
});

it('bloqueia alterar permissões da role protegida', function () {
    $super = Role::where('name', 'super-admin')->where('guard_name', 'admin')->firstOrFail();

    expect(fn () => app(SyncPermissoesPerfilAction::class)->execute(
        SyncPermissoesPerfilDTO::fromArray(['roleId' => $super->id, 'permissoes' => ['dashboard.view']]),
    ))->toThrow(AccessException::class);
});

it('ignora permissões fora do catálogo ao sincronizar perfil', function () {
    $role = Role::findOrCreate('editor', 'admin');

    $resultado = app(SyncPermissoesPerfilAction::class)->execute(
        SyncPermissoesPerfilDTO::fromArray([
            'roleId' => $role->id,
            'permissoes' => ['dashboard.view', 'inexistente.xyz'],
        ]),
    );

    expect($resultado->load('permissions')->permissions->pluck('name')->all())->toBe(['dashboard.view']);
});
