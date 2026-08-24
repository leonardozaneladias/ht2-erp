<?php

declare(strict_types=1);

use HT2ML\Core\Models\Concerns\BelongsToEmpresa;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Model de negócio fictício para exercitar o isolamento de dados por empresa.
 */
class DocTenantStub extends Model
{
    use BelongsToEmpresa;

    protected $table = 'doc_tenant_stub';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('doc_tenant_stub', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('empresa_id');
        $table->string('titulo');
        $table->timestamps();
    });
});

it('isola dados de negócio entre empresas e não vaza ao trocar de contexto', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    $ctx = app(TenantContext::class);

    $ctx->definirEmpresa($a->id);
    DocTenantStub::create(['titulo' => 'Doc A']);

    $ctx->definirEmpresa($b->id);
    DocTenantStub::create(['titulo' => 'Doc B']);

    expect(DocTenantStub::pluck('titulo')->all())->toBe(['Doc B']);

    $ctx->definirEmpresa($a->id);
    expect(DocTenantStub::pluck('titulo')->all())->toBe(['Doc A']);

    // Escape consciente (relatórios cross-empresa autorizados) enxerga tudo.
    expect(DocTenantStub::withoutGlobalScope('empresa')->count())->toBe(2);
});

it('a permissão de um papel por empresa só vale na empresa onde foi atribuída (via Gate)', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    $gestor = Role::create(['name' => 'gestor', 'guard_name' => 'admin']);
    $gestor->givePermissionTo(Permission::create(['name' => 'docs.ver', 'guard_name' => 'admin']));

    $user = criarAdminUser('u@teste.com');
    $user->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $a->id]);

    $ctx = app(TenantContext::class);

    $ctx->definirEmpresa($a->id);
    expect($user->can('docs.ver'))->toBeTrue();

    $ctx->definirEmpresa($b->id);
    expect($user->can('docs.ver'))->toBeFalse();
});

it('papéis por empresa de um usuário não vazam para outro', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $gestor = Role::create(['name' => 'gestor', 'guard_name' => 'admin']);
    $gestor->givePermissionTo(Permission::create(['name' => 'docs.ver', 'guard_name' => 'admin']));

    $comAcesso = criarAdminUser('com@teste.com');
    $comAcesso->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $a->id]);
    $semAcesso = criarAdminUser('sem@teste.com');

    app(TenantContext::class)->definirEmpresa($a->id);

    expect($comAcesso->can('docs.ver'))->toBeTrue()
        ->and($semAcesso->can('docs.ver'))->toBeFalse();
});

it('sem empresa ativa, o papel por empresa não concede acesso', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $gestor = Role::create(['name' => 'gestor', 'guard_name' => 'admin']);
    $gestor->givePermissionTo(Permission::create(['name' => 'docs.ver', 'guard_name' => 'admin']));

    $user = criarAdminUser('u@teste.com');
    $user->papeisPorEmpresa()->attach($gestor->id, ['empresa_id' => $a->id]);

    app(TenantContext::class)->limpar();

    expect($user->can('docs.ver'))->toBeFalse();
});
