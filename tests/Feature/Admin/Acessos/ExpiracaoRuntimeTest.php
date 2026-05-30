<?php

declare(strict_types=1);

use App\Enums\TipoConcessao;
use App\Services\Admin\AccessResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->resolver = app(AccessResolver::class);
});

it('ignora grant vencido mesmo sem rodar o job de expiração', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant, now()->subMinute()->toDateTimeString());

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeNull();
});

it('respeita grant com expiração futura', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant, now()->addDay()->toDateTimeString());

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
});

it('ignora deny vencido — volta a valer a permissão da role', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor'); // usuarios.listar

    concederAcessoDireto($user, 'usuarios.listar', TipoConcessao::Deny, now()->subMinute()->toDateTimeString());

    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeTrue();
});

it('grant sem expiração é permanente', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant, null);

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
});
