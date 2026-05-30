<?php

declare(strict_types=1);

use App\Exceptions\AccessException;
use App\Support\Access\AccessGuard;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->guard = app(AccessGuard::class);
});

it('bloqueia remover a role do último super-admin', function () {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    expect(fn () => $this->guard->garantirNaoEsvaziarSuperAdmins($super, []))
        ->toThrow(AccessException::class);
});

it('permite remover super-admin se houver outro ativo', function () {
    $s1 = criarAdminUser('s1@teste.com');
    $s1->assignRole('super-admin');
    $s2 = criarAdminUser('s2@teste.com');
    $s2->assignRole('super-admin');

    expect(fn () => $this->guard->garantirNaoEsvaziarSuperAdmins($s1, []))
        ->not->toThrow(AccessException::class);
});

it('bloqueia desativar o último super-admin', function () {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    expect(fn () => $this->guard->garantirPodeDesativar($super))
        ->toThrow(AccessException::class);
});

it('bloqueia alterar role protegida', function () {
    expect(fn () => $this->guard->garantirRoleNaoProtegida('super-admin'))
        ->toThrow(AccessException::class);
});

it('permite alterar roles comuns', function () {
    expect(fn () => $this->guard->garantirRoleNaoProtegida('gestor'))
        ->not->toThrow(AccessException::class);
});

it('bloqueia deny em super-admin', function () {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    expect(fn () => $this->guard->garantirDenyNaoEmSuperAdmin($super))
        ->toThrow(AccessException::class);
});

it('impede o ator de remover de si mesmo as permissões de gestão de acesso', function () {
    $atual = Role::findOrCreate('admin-acesso', 'admin');
    $atual->givePermissionTo(['acessos.gerenciar', 'permissoes.gerenciar']);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('admin-acesso');

    expect(fn () => $this->guard->garantirAutoGestao($ator, $ator, ['gestor']))
        ->toThrow(AccessException::class);
});

it('permite ao ator trocar de role mantendo as permissões de auto-gestão', function () {
    $origem = Role::findOrCreate('admin-acesso', 'admin');
    $origem->givePermissionTo(['acessos.gerenciar', 'permissoes.gerenciar']);
    $destino = Role::findOrCreate('admin-acesso-2', 'admin');
    $destino->givePermissionTo(['acessos.gerenciar', 'permissoes.gerenciar']);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('admin-acesso');

    expect(fn () => $this->guard->garantirAutoGestao($ator, $ator, ['admin-acesso-2']))
        ->not->toThrow(AccessException::class);
});
