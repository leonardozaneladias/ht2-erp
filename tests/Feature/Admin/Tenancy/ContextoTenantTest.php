<?php

declare(strict_types=1);

use App\Http\Middleware\DefinirContextoTenant;
use HT2ML\Core\Actions\Admin\DefinirEmpresaAtivaAction;
use HT2ML\Core\Actions\Admin\DefinirFilialAtivaAction;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Core\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function empresaAtiva(string $nome): Empresa
{
    return Empresa::create(['nome' => $nome, 'ativo' => true]);
}

function adminComEmpresa(Empresa $empresa, bool $todasFiliais = true): HT2ML\Core\Models\AdminUser
{
    $user = criarAdminUser('ctx@teste.com');
    $user->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => $todasFiliais]);

    return $user;
}

it('lista empresas disponíveis do usuário comum (apenas ativas concedidas)', function () {
    $a = empresaAtiva('A');
    $inativa = Empresa::create(['nome' => 'Z', 'ativo' => false]);
    empresaAtiva('Outra'); // não concedida

    $user = adminComEmpresa($a);
    $user->empresasAcessiveis()->attach($inativa->id, ['todas_filiais' => true]);

    expect(app(TenantResolver::class)->empresasDisponiveis($user)->pluck('nome')->all())->toBe(['A']);
});

it('super-admin enxerga todas as empresas ativas', function () {
    empresaAtiva('A');
    empresaAtiva('B');
    Empresa::create(['nome' => 'Inativa', 'ativo' => false]);

    Role::create(['name' => 'super-admin', 'guard_name' => 'admin']);
    $user = criarAdminUser('sa@teste.com');
    $user->assignRole('super-admin');

    expect(app(TenantResolver::class)->empresasDisponiveis($user)->pluck('nome')->all())->toBe(['A', 'B']);
});

it('define empresa ativa, persiste na coluna e atualiza o contexto', function () {
    $a = empresaAtiva('A');
    $user = adminComEmpresa($a);

    app(DefinirEmpresaAtivaAction::class)->execute($user, $a->id);

    expect($user->fresh()->empresa_ativa_id)->toBe($a->id)
        ->and(app(TenantContext::class)->empresaAtivaId())->toBe($a->id);
});

it('ignora empresa sem acesso ao defini-la', function () {
    $a = empresaAtiva('A');
    $user = criarAdminUser('semacesso@teste.com');

    app(DefinirEmpresaAtivaAction::class)->execute($user, $a->id);

    expect($user->fresh()->empresa_ativa_id)->toBeNull();
});

it('trocar de empresa reseta a filial ativa', function () {
    $a = empresaAtiva('A');
    $b = empresaAtiva('B');
    $user = criarAdminUser('multi@teste.com');
    $user->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);
    $user->empresasAcessiveis()->attach($b->id, ['todas_filiais' => true]);
    $filialA = $a->filiais()->create(['nome' => 'Matriz A', 'e_matriz' => true]);

    app(DefinirEmpresaAtivaAction::class)->execute($user, $a->id);
    app(DefinirFilialAtivaAction::class)->execute($user, $filialA->id);
    expect(app(TenantContext::class)->filialAtivaId())->toBe($filialA->id);

    app(DefinirEmpresaAtivaAction::class)->execute($user, $b->id);
    expect(app(TenantContext::class)->empresaAtivaId())->toBe($b->id)
        ->and(app(TenantContext::class)->filialAtivaId())->toBeNull();
});

it('middleware define a empresa padrão quando o contexto está vazio', function () {
    $a = empresaAtiva('A');
    $user = adminComEmpresa($a);

    $this->actingAs($user, 'admin');
    app(TenantContext::class)->limpar();

    app(DefinirContextoTenant::class)->handle(
        Request::create('/admin/dashboard'),
        fn (Request $r): Response => new Response,
    );

    expect(app(TenantContext::class)->empresaAtivaId())->toBe($a->id);
});

it('middleware descarta empresa de sessão não mais acessível', function () {
    $a = empresaAtiva('A');
    $b = empresaAtiva('B');
    $user = adminComEmpresa($a); // só tem acesso a A

    $this->actingAs($user, 'admin');
    app(TenantContext::class)->definirEmpresa($b->id); // sessão aponta para B (sem acesso)

    app(DefinirContextoTenant::class)->handle(
        Request::create('/admin/dashboard'),
        fn (Request $r): Response => new Response,
    );

    expect(app(TenantContext::class)->empresaAtivaId())->toBe($a->id);
});
