<?php

declare(strict_types=1);

use App\Services\Admin\AccessResolver;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Publica o catálogo (inclui exemplos.listar) para os papéis/grants poderem
    // referenciar a permissão.
    Artisan::call('access:sync');

    $this->resolver = app(AccessResolver::class);
    $this->a = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $this->b = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
});

it('super-admin tem acesso em qualquer empresa (bypass)', function () {
    $user = criarAdminUser('super@teste.com');
    criarRoleAdmin('super-admin', 100);
    $user->assignRole('super-admin');

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeTrue()
        ->and($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->b->id))->toBeTrue();
});

it('papel por empresa só concede na empresa onde foi atribuído', function () {
    $gestor = criarRoleAdmin('gestor', 50);
    $gestor->givePermissionTo(Permission::findOrCreate('exemplos.listar', 'admin'));

    $user = criarAdminUser('u@teste.com');
    $user->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $this->a->id]);

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeTrue()
        ->and($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->b->id))->toBeFalse();
});

it('papel global concede em todas as empresas', function () {
    $gestor = criarRoleAdmin('gestor', 50);
    $gestor->givePermissionTo(Permission::findOrCreate('exemplos.listar', 'admin'));

    $user = criarAdminUser('u@teste.com');
    $user->assignRole('gestor');

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeTrue()
        ->and($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->b->id))->toBeTrue();
});

it('deny direto vence o papel por empresa', function () {
    $gestor = criarRoleAdmin('gestor', 50);
    $gestor->givePermissionTo(Permission::findOrCreate('exemplos.listar', 'admin'));

    $user = criarAdminUser('u@teste.com');
    $user->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $this->a->id]);
    concederAcessoDireto($user, 'exemplos.listar', TipoConcessao::Deny);

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeFalse();
});

it('grant direto concede mesmo sem papel na empresa', function () {
    $user = criarAdminUser('u@teste.com');
    concederAcessoDireto($user, 'exemplos.listar', TipoConcessao::Grant);

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeTrue();
});

it('sem papel, grant ou super-admin, nega', function () {
    $user = criarAdminUser('u@teste.com');

    expect($this->resolver->permiteNaEmpresa($user, 'exemplos.listar', $this->a->id))->toBeFalse();
});
