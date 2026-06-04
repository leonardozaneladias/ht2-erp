<?php

declare(strict_types=1);

use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function novaRoleAdmin(string $nome): Role
{
    return Role::create(['name' => $nome, 'guard_name' => 'admin']);
}

it('atribui papel a um usuário no escopo de uma empresa', function () {
    $empresa = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $role = novaRoleAdmin('gestor');
    $user = criarAdminUser('u@teste.com');

    $user->papeisPorEmpresa()->attach($role->id, ['empresa_id' => $empresa->id]);

    expect($user->rolesNaEmpresa($empresa->id)->pluck('name')->all())->toBe(['gestor']);
});

it('isola papéis por empresa (gestor em A, operador em B)', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    $user = criarAdminUser('u@teste.com');

    $user->papeisPorEmpresa()->attach(novaRoleAdmin('gestor')->id, ['empresa_id' => $a->id]);
    $user->papeisPorEmpresa()->attach(novaRoleAdmin('operador')->id, ['empresa_id' => $b->id]);

    expect($user->rolesNaEmpresa($a->id)->pluck('name')->all())->toBe(['gestor'])
        ->and($user->rolesNaEmpresa($b->id)->pluck('name')->all())->toBe(['operador']);
});

it('não confunde papel global (spatie) com papel por empresa', function () {
    $empresa = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $user = criarAdminUser('u@teste.com');

    novaRoleAdmin('super-admin');
    $user->assignRole('super-admin');
    $user->papeisPorEmpresa()->attach(novaRoleAdmin('gestor')->id, ['empresa_id' => $empresa->id]);

    // Papel global vive em roles() (spatie); por empresa vive na dimensão própria.
    expect($user->roles->pluck('name')->all())->toBe(['super-admin'])
        ->and($user->rolesNaEmpresa($empresa->id)->pluck('name')->all())->toBe(['gestor']);
});
