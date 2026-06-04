<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Model de teste que usa o trait de tenancy (não há módulos de negócio ainda).
 */
class ItemTenantStub extends Model
{
    use BelongsToEmpresa;

    protected $table = 'itens_tenant_stub';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('itens_tenant_stub', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('empresa_id');
        $table->string('nome');
        $table->timestamps();
    });
});

function novaEmpresaTeste(string $nome = 'Empresa Teste'): Empresa
{
    return Empresa::create(['nome' => $nome, 'ativo' => true]);
}

it('relaciona empresa e filiais', function () {
    $empresa = novaEmpresaTeste();
    $matriz = $empresa->filiais()->create(['nome' => 'Matriz', 'e_matriz' => true]);

    expect($empresa->filiais()->count())->toBe(1)
        ->and($matriz->empresa->id)->toBe($empresa->id);
});

it('dá acesso à empresa com todas as filiais', function () {
    $empresa = novaEmpresaTeste();
    $filial = $empresa->filiais()->create(['nome' => 'Filial 1']);

    $user = criarAdminUser('acesso@teste.com');
    $user->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);

    expect($user->temAcessoAEmpresa($empresa->id))->toBeTrue()
        ->and($user->temAcessoAFilial($filial->id))->toBeTrue();
});

it('restringe a filiais específicas quando não são todas', function () {
    $empresa = novaEmpresaTeste();
    $f1 = $empresa->filiais()->create(['nome' => 'F1']);
    $f2 = $empresa->filiais()->create(['nome' => 'F2']);

    $user = criarAdminUser('acesso@teste.com');
    $user->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => false]);
    $user->filiaisAcessiveis()->attach($f1->id);

    expect($user->temAcessoAFilial($f1->id))->toBeTrue()
        ->and($user->temAcessoAFilial($f2->id))->toBeFalse();
});

it('nega acesso a empresa não concedida', function () {
    $empresa = novaEmpresaTeste();

    expect(criarAdminUser('x@teste.com')->temAcessoAEmpresa($empresa->id))->toBeFalse();
});

it('gerencia o contexto e reseta a filial ao trocar de empresa', function () {
    $ctx = app(TenantContext::class);
    $ctx->definirEmpresa(10);
    $ctx->definirFilial(20);

    expect($ctx->empresaAtivaId())->toBe(10)
        ->and($ctx->filialAtivaId())->toBe(20);

    $ctx->definirEmpresa(11);

    expect($ctx->empresaAtivaId())->toBe(11)
        ->and($ctx->filialAtivaId())->toBeNull();
});

it('isola registros por empresa (global scope) e preenche empresa_id ao criar', function () {
    $a = novaEmpresaTeste('A');
    $b = novaEmpresaTeste('B');
    $ctx = app(TenantContext::class);

    $ctx->definirEmpresa($a->id);
    ItemTenantStub::create(['nome' => 'Item A']);

    $ctx->definirEmpresa($b->id);
    ItemTenantStub::create(['nome' => 'Item B']);

    // Contexto B só enxerga registros de B.
    expect(ItemTenantStub::count())->toBe(1)
        ->and(ItemTenantStub::first()->nome)->toBe('Item B');

    // Trocar para A só enxerga A.
    $ctx->definirEmpresa($a->id);
    expect(ItemTenantStub::count())->toBe(1)
        ->and(ItemTenantStub::first()->nome)->toBe('Item A');

    // Sem o global scope, vê ambos (auto-preenchimento de empresa_id funcionou).
    expect(ItemTenantStub::withoutGlobalScope('empresa')->count())->toBe(2);
});
