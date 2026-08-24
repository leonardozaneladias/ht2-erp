<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Services\Admin\AccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->resolver = app(AccessResolver::class);
});

it('super-admin tem acesso a tudo (bypass)', function () {
    $user = criarAdminUser();
    $user->assignRole('super-admin');

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
    expect($this->resolver->decide($user, 'qualquer.coisa.inexistente'))->toBeTrue();
});

it('permissão via role retorna true', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // gestor tem usuarios.listar

    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeTrue();
});

it('ausência de role/grant retorna null (sem opinião)', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    expect($this->resolver->decide($user, 'usuarios.deletar'))->toBeNull();
});

it('grant direto concede permissão que a role não dá', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant);

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
});

it('deny direto vence a permissão concedida pela role', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // tem usuarios.listar

    concederAcessoDireto($user, 'usuarios.listar', TipoConcessao::Deny);

    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeFalse();
});

it('deny vence grant direto para a mesma permissão', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant);
    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Deny);

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeFalse();
});

it('deny não afeta super-admin', function () {
    $user = criarAdminUser();
    $user->assignRole('super-admin');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Deny);

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
});

it('permissoesEfetivas é a união das roles e grants menos os denies', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // dashboard.view + usuarios.listar

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant);
    concederAcessoDireto($user, 'usuarios.listar', TipoConcessao::Deny);

    $efetivas = $this->resolver->permissoesEfetivas($user)->all();

    expect($efetivas)->toContain('dashboard.view', 'usuarios.criar');
    expect($efetivas)->not->toContain('usuarios.listar');
});
