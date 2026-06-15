<?php

declare(strict_types=1);

use App\Livewire\Admin\Produtos\ProdutoTable;
use App\Models\AdminUser;
use App\Models\Empresa;
use App\Models\Produto;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PowerComponents\LivewirePowerGrid\Column;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('access:sync');
});

/**
 * Cria empresa ativa + 1 produto identificável dentro dela (via tenant context).
 *
 * @return array{0: Empresa, 1: Produto}
 */
function empresaComProduto(string $nome): array
{
    $empresa = Empresa::factory()->create(['nome' => $nome, 'ativo' => true]);
    app(TenantContext::class)->definirEmpresa($empresa->id);
    $produto = Produto::factory()->create(['nome' => "Produto {$nome}"]);

    return [$empresa, $produto];
}

/**
 * Monta um usuário com (opcionalmente) a capacidade multi-empresa, acesso às
 * empresas informadas e o papel `produtos.listar` apenas nas empresas elegíveis.
 *
 * @param  list<Empresa>  $acessiveis
 * @param  list<Empresa>  $elegiveis
 */
function usuarioMultiEmpresa(array $acessiveis, array $elegiveis, bool $comCapacidade = true): AdminUser
{
    $operador = Role::findOrCreate('operador', 'admin');
    $operador->givePermissionTo(Permission::findOrCreate('produtos.listar', 'admin'));

    $user = criarAdminUser('multi@teste.com');

    if ($comCapacidade) {
        $gestor = Role::findOrCreate('gestor', 'admin');
        $gestor->givePermissionTo(Permission::findOrCreate('listagens.multi_empresa', 'admin'));
        $user->assignRole('gestor');
    }

    foreach ($acessiveis as $empresa) {
        $user->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);
    }

    foreach ($elegiveis as $empresa) {
        $user->papeisPorEmpresa()->attach($operador->id, ['empresa_id' => $empresa->id]);
    }

    return $user;
}

/** @return list<string> */
function camposDasColunas(ProdutoTable $tabela): array
{
    return collect($tabela->columns())
        ->map(static fn (Column $c): mixed => data_get($c, 'field'))
        ->all();
}

it('sem a permissão listagens.multi_empresa, o recurso não aparece', function () {
    [$a] = empresaComProduto('A');
    [$b] = empresaComProduto('B');

    $user = usuarioMultiEmpresa([$a, $b], [$a, $b], comCapacidade: false);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    expect(camposDasColunas($tabela))->not->toContain('empresa_nome');
});

it('com apenas 1 empresa elegível, o recurso não aparece', function () {
    [$a] = empresaComProduto('A');
    [$b] = empresaComProduto('B');

    // Acessa A e B, mas só tem produtos.listar em A → 1 elegível.
    $user = usuarioMultiEmpresa([$a, $b], [$a]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    expect(camposDasColunas($tabela))->not->toContain('empresa_nome');
});

it('com 2+ empresas elegíveis, mostra a coluna Empresa e o filtro', function () {
    [$a] = empresaComProduto('A');
    [$b] = empresaComProduto('B');

    $user = usuarioMultiEmpresa([$a, $b], [$a, $b]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    expect(camposDasColunas($tabela))->toContain('empresa_nome');

    $filtroEmpresa = collect($tabela->filters())
        ->first(static fn ($f): bool => $f->column === 'empresa_nome');

    expect($filtroEmpresa)->not->toBeNull();
});

it('RBAC estrito: empresa acessível sem produtos.listar não é elegível', function () {
    [$a] = empresaComProduto('A');
    [$b] = empresaComProduto('B');
    [$c] = empresaComProduto('C');

    // Acessa A, B e C; mas só tem produtos.listar em A e B.
    $user = usuarioMultiEmpresa([$a, $b, $c], [$a, $b]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    $filtroEmpresa = collect($tabela->filters())
        ->first(static fn ($f): bool => $f->column === 'empresa_nome');

    $ids = collect($filtroEmpresa->dataSource)->pluck('id')->all();

    expect($ids)->toContain($a->id, $b->id)
        ->not->toContain($c->id);
});

it('por padrão, sem seleção, mostra só a empresa ativa', function () {
    [$a, $produtoA] = empresaComProduto('A');
    [$b, $produtoB] = empresaComProduto('B');

    $user = usuarioMultiEmpresa([$a, $b], [$a, $b]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    Livewire::test(ProdutoTable::class)
        ->assertSee($produtoA->nome)
        ->assertDontSee($produtoB->nome);
});

it('ao selecionar 2 empresas, traz registros das duas', function () {
    [$a, $produtoA] = empresaComProduto('A');
    [$b, $produtoB] = empresaComProduto('B');

    $user = usuarioMultiEmpresa([$a, $b], [$a, $b]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    Livewire::test(ProdutoTable::class)
        ->set('filters.multi_select.empresa_id', [$a->id, $b->id])
        ->assertSee($produtoA->nome)
        ->assertSee($produtoB->nome);
});

it('injeção: selecionar empresa sem acesso não vaza linhas (cai na empresa ativa)', function () {
    [$a, $produtoA] = empresaComProduto('A');
    [$b, $produtoB] = empresaComProduto('B');
    [$c, $produtoC] = empresaComProduto('C');

    // Elegível só em A e B; C é acessível mas não elegível (sem produtos.listar).
    $user = usuarioMultiEmpresa([$a, $b, $c], [$a, $b]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    Livewire::test(ProdutoTable::class)
        ->set('filters.multi_select.empresa_id', [$c->id])
        ->assertSee($produtoA->nome)
        ->assertDontSee($produtoC->nome)
        ->assertDontSee($produtoB->nome);
});

it('super-admin enxerga o recurso quando há 2+ empresas', function () {
    [$a] = empresaComProduto('A');
    [$b] = empresaComProduto('B');

    $super = criarAdminUser('super@teste.com');
    Role::findOrCreate('super-admin', 'admin');
    $super->assignRole('super-admin');

    $this->actingAs($super, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    expect(camposDasColunas($tabela))->toContain('empresa_nome');
});
