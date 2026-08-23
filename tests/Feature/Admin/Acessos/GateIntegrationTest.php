<?php

declare(strict_types=1);

use App\Services\Admin\AccessResolver;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('can() reflete a permissão concedida pela role', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    expect($user->can('usuarios.listar'))->toBeTrue();
    expect($user->can('usuarios.deletar'))->toBeFalse();
});

it('deny direto bloqueia can() de permissão nomeada', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');
    concederAcessoDireto($user, 'usuarios.listar', TipoConcessao::Deny);

    expect($user->can('usuarios.listar'))->toBeFalse();
});

it('super-admin passa em qualquer ability via Gate', function () {
    $user = criarAdminUser();
    $user->assignRole('super-admin');

    expect($user->can('usuarios.deletar'))->toBeTrue();
    expect(Gate::forUser($user)->allows('qualquer.coisa'))->toBeTrue();
});

it('deny em permissão nomeada bloqueia a Policy que depende dela', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // tem usuarios.listar (AdminUserPolicy::viewAny)

    expect($user->can('viewAny', AdminUser::class))->toBeTrue();

    concederAcessoDireto($user, 'usuarios.listar', TipoConcessao::Deny);
    app(AccessResolver::class)->invalidar($user);

    expect($user->can('viewAny', AdminUser::class))->toBeFalse();
});
