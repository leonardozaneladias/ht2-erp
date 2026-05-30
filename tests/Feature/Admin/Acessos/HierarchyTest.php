<?php

declare(strict_types=1);

use App\Services\Admin\HierarchyResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->hierarchy = app(HierarchyResolver::class);
});

function criarRoleComNivel(string $nome, int $nivel): Role
{
    $role = Role::findOrCreate($nome, 'admin');
    DB::table('roles')->where('id', $role->id)->update(['nivel' => $nivel]);

    return $role;
}

it('super-admin está no topo da hierarquia', function () {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');

    expect($this->hierarchy->nivelDe($super))->toBe(PHP_INT_MAX);
    expect($this->hierarchy->podeGerir($super, $gestor))->toBeTrue();
});

it('nível do usuário é o maior entre suas roles', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // nível 50

    expect($this->hierarchy->nivelDe($user))->toBe(50);
});

it('gestor não pode gerir outro de nível igual', function () {
    $a = criarAdminUser('a@teste.com');
    $a->assignRole('gestor');
    $b = criarAdminUser('b@teste.com');
    $b->assignRole('gestor');

    expect($this->hierarchy->podeGerir($a, $b))->toBeFalse();
});

it('gestor pode gerir usuário de nível inferior', function () {
    criarRoleComNivel('analista', 10);

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor'); // 50
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('analista'); // 10

    expect($this->hierarchy->podeGerir($gestor, $alvo))->toBeTrue();
});

it('rolesGerenciaveis traz apenas roles de nível abaixo do ator', function () {
    criarRoleComNivel('analista', 10);

    $gestor = criarAdminUser();
    $gestor->assignRole('gestor'); // 50

    $nomes = $this->hierarchy->rolesGerenciaveis($gestor)->pluck('name')->all();

    expect($nomes)->toContain('analista');
    expect($nomes)->not->toContain('gestor');
    expect($nomes)->not->toContain('super-admin');
});
