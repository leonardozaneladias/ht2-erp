<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Services\Admin\AccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->resolver = app(AccessResolver::class);

    $this->rh = Role::findOrCreate('rh', 'admin');
    $this->rh->givePermissionTo('usuarios.listar');

    $this->financeiro = Role::findOrCreate('financeiro', 'admin');
    $this->financeiro->givePermissionTo('configuracoes.editar');
});

it('sem perfil ativo, o acesso é a soma de todas as roles', function () {
    $user = criarAdminUser();
    $user->assignRole(['rh', 'financeiro']);
    $this->actingAs($user, 'admin');

    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeTrue();
    expect($this->resolver->decide($user, 'configuracoes.editar'))->toBeTrue();
});

it('com perfil ativo, restringe às permissões da role ativa', function () {
    $user = criarAdminUser();
    $user->assignRole(['rh', 'financeiro']);
    $this->actingAs($user, 'admin');
    $this->session(['admin.perfil_ativo_role_id' => $this->rh->id]);

    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeTrue();
    expect($this->resolver->decide($user, 'configuracoes.editar'))->toBeNull();
});

it('grant direto continua valendo mesmo com perfil ativo restrito', function () {
    $user = criarAdminUser();
    $user->assignRole('rh');
    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant);

    $this->actingAs($user, 'admin');
    $this->session(['admin.perfil_ativo_role_id' => $this->rh->id]);

    expect($this->resolver->decide($user, 'usuarios.criar'))->toBeTrue();
    expect($this->resolver->decide($user, 'usuarios.listar'))->toBeTrue();
});

it('a lente de perfil ativo não afeta a resolução de outro usuário', function () {
    $logado = criarAdminUser('logado@teste.com');
    $logado->assignRole(['rh', 'financeiro']);
    $this->actingAs($logado, 'admin');
    $this->session(['admin.perfil_ativo_role_id' => $this->rh->id]);

    $outro = criarAdminUser('outro@teste.com');
    $outro->assignRole(['rh', 'financeiro']);

    // o perfil ativo do logado não restringe o "outro"
    expect($this->resolver->decide($outro, 'configuracoes.editar'))->toBeTrue();
});
