<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\SyncRolesEmpresaAction;
use HT2ML\Core\DTOs\Admin\SyncRolesEmpresaDTO;
use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Services\Admin\HierarchyResolver;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function roleNivel(string $nome, int $nivel): Role
{
    $role = Role::create(['name' => $nome, 'guard_name' => 'admin']);
    $role->forceFill(['nivel' => $nivel])->save();

    return $role;
}

function superAdminAtor(string $email = 'ator@teste.com'): AdminUser
{
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $ator = criarAdminUser($email);
    $ator->assignRole('super-admin');

    return $ator;
}

it('sincroniza papéis na empresa de forma isolada e substitui no re-sync', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    roleNivel('gestor', 50);
    roleNivel('operador', 10);
    $alvo = criarAdminUser('alvo@teste.com');
    $ator = superAdminAtor();

    app(SyncRolesEmpresaAction::class)->execute(
        SyncRolesEmpresaDTO::fromArray(['adminUserId' => $alvo->id, 'empresaId' => $a->id, 'roles' => ['gestor']]),
        $ator,
    );

    expect($alvo->rolesNaEmpresa($a->id)->pluck('name')->all())->toBe(['gestor'])
        ->and($alvo->rolesNaEmpresa($b->id)->pluck('name')->all())->toBe([]);

    app(SyncRolesEmpresaAction::class)->execute(
        SyncRolesEmpresaDTO::fromArray(['adminUserId' => $alvo->id, 'empresaId' => $a->id, 'roles' => ['operador']]),
        $ator,
    );

    expect($alvo->rolesNaEmpresa($a->id)->pluck('name')->all())->toBe(['operador']);
});

it('ignora papéis protegidos (super-admin) no escopo por empresa', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    roleNivel('gestor', 50);
    $alvo = criarAdminUser('alvo@teste.com');
    $ator = superAdminAtor();

    app(SyncRolesEmpresaAction::class)->execute(
        SyncRolesEmpresaDTO::fromArray([
            'adminUserId' => $alvo->id,
            'empresaId' => $a->id,
            'roles' => ['super-admin', 'gestor'],
        ]),
        $ator,
    );

    expect($alvo->rolesNaEmpresa($a->id)->pluck('name')->all())->toBe(['gestor'])
        ->and($alvo->fresh()->hasRole('super-admin'))->toBeFalse();
});

it('respeita a hierarquia por empresa ao atribuir papel acima do nível do ator', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $gestor = roleNivel('gestor', 50);
    roleNivel('diretor', 60);
    $ator = criarAdminUser('ator@teste.com');
    $ator->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $a->id]);
    $alvo = criarAdminUser('alvo@teste.com');

    app(TenantContext::class)->definirEmpresa($a->id);

    expect(app(HierarchyResolver::class)->nivelDe($ator))->toBe(50);

    expect(fn () => app(SyncRolesEmpresaAction::class)->execute(
        SyncRolesEmpresaDTO::fromArray(['adminUserId' => $alvo->id, 'empresaId' => $a->id, 'roles' => ['diretor']]),
        $ator,
    ))->toThrow(AccessException::class);
});
