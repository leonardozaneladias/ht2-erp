<?php

declare(strict_types=1);

use App\Livewire\Admin\Exemplos\ExemploTable;
use App\Livewire\Admin\Produtos\ProdutoTable;
use App\Models\AdminUser;
use App\Models\Empresa;
use App\Models\Exemplo;
use App\Models\Filial;
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

function filialDe(Empresa $empresa, string $nome): Filial
{
    return Filial::factory()->create([
        'empresa_id' => $empresa->id,
        'nome' => $nome,
        'ativo' => true,
    ]);
}

function exemploEm(Empresa $empresa, string $nome, ?Filial $filial = null): Exemplo
{
    app(TenantContext::class)->definirEmpresa($empresa->id);

    return Exemplo::factory()->create([
        'nome' => $nome,
        'filial_id' => $filial?->id,
    ]);
}

/**
 * Usuário capaz (listagens.multi_empresa) e com exemplos.listar nas empresas
 * elegíveis. $todasFiliais indica, por empresa, se recebe "todas as filiais".
 *
 * @param  array<int, array{empresa: Empresa, todasFiliais?: bool, filiais?: list<Filial>}>  $acessos
 */
function gestorComExemplos(array $acessos): AdminUser
{
    $operador = Role::findOrCreate('operador-exemplos', 'admin');
    $operador->givePermissionTo(Permission::findOrCreate('exemplos.listar', 'admin'));

    $gestor = Role::findOrCreate('gestor', 'admin');
    $gestor->givePermissionTo(Permission::findOrCreate('listagens.multi_empresa', 'admin'));

    $user = criarAdminUser('gestor-exemplos@teste.com');
    $user->assignRole('gestor');

    foreach ($acessos as $acesso) {
        $empresa = $acesso['empresa'];
        $todasFiliais = $acesso['todasFiliais'] ?? true;

        $user->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => $todasFiliais]);
        $user->papeisPorEmpresa()->attach($operador->id, ['empresa_id' => $empresa->id]);

        foreach ($acesso['filiais'] ?? [] as $filial) {
            $user->filiaisAcessiveis()->attach($filial->id);
        }
    }

    return $user;
}

/** @return list<string> */
function camposDaTabela(object $tabela): array
{
    return collect($tabela->columns())
        ->map(static fn (Column $c): mixed => data_get($c, 'field'))
        ->all();
}

it('Exemplo expõe a coluna e o filtro de filial quando o multi-empresa está ativo', function () {
    $a = Empresa::factory()->create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::factory()->create(['nome' => 'Empresa B', 'ativo' => true]);

    $user = gestorComExemplos([['empresa' => $a], ['empresa' => $b]]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ExemploTable::class)->instance();

    expect(camposDaTabela($tabela))->toContain('empresa_nome', 'filial_nome');

    $filtroFilial = collect($tabela->filters())
        ->first(static fn ($f): bool => $f->column === 'filial_nome');

    expect($filtroFilial)->not->toBeNull();
});

it('Produto (sem filial_id) não expõe a dimensão filial', function () {
    $a = Empresa::factory()->create(['nome' => 'Empresa A', 'ativo' => true]);
    Empresa::factory()->create(['nome' => 'Empresa B', 'ativo' => true]);

    $super = criarAdminUser('super@teste.com');
    Role::findOrCreate('super-admin', 'admin');
    $super->assignRole('super-admin');

    $this->actingAs($super, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ProdutoTable::class)->instance();

    expect(camposDaTabela($tabela))->toContain('empresa_nome')
        ->not->toContain('filial_nome');
});

it('o filtro de filial lista apenas as filiais acessíveis', function () {
    $a = Empresa::factory()->create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::factory()->create(['nome' => 'Empresa B', 'ativo' => true]);

    $a1 = filialDe($a, 'Filial A1');
    $a2 = filialDe($a, 'Filial A2');
    $b1 = filialDe($b, 'Filial B1');
    $b2 = filialDe($b, 'Filial B2');

    // Em A: todas as filiais. Em B: apenas a filial B1 (acesso específico).
    $user = gestorComExemplos([
        ['empresa' => $a, 'todasFiliais' => true],
        ['empresa' => $b, 'todasFiliais' => false, 'filiais' => [$b1]],
    ]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    $tabela = Livewire::test(ExemploTable::class)->instance();

    $filtroFilial = collect($tabela->filters())
        ->first(static fn ($f): bool => $f->column === 'filial_nome');

    $ids = collect($filtroFilial->dataSource)->pluck('id')->all();

    expect($ids)->toContain($a1->id, $a2->id, $b1->id)
        ->not->toContain($b2->id);
});

it('o escopo por filial filtra os registros da filial selecionada', function () {
    $a = Empresa::factory()->create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::factory()->create(['nome' => 'Empresa B', 'ativo' => true]);

    $a1 = filialDe($a, 'Filial A1');
    $a2 = filialDe($a, 'Filial A2');

    $exemploA1 = exemploEm($a, 'Exemplo na A1', $a1);
    $exemploA2 = exemploEm($a, 'Exemplo na A2', $a2);

    $user = gestorComExemplos([['empresa' => $a], ['empresa' => $b]]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    Livewire::test(ExemploTable::class)
        ->set('filters.multi_select.empresa_id', [$a->id])
        ->set('filters.multi_select.filial_id', [$a1->id])
        ->assertSee($exemploA1->nome)
        ->assertDontSee($exemploA2->nome);
});

it('injeção: filial de empresa não selecionada é contida pelo escopo de empresa', function () {
    $a = Empresa::factory()->create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::factory()->create(['nome' => 'Empresa B', 'ativo' => true]);

    $a1 = filialDe($a, 'Filial A1');
    $b1 = filialDe($b, 'Filial B1');

    $exemploA1 = exemploEm($a, 'Exemplo na A1', $a1);
    $exemploB1 = exemploEm($b, 'Exemplo na B1', $b1);

    $user = gestorComExemplos([['empresa' => $a], ['empresa' => $b]]);

    $this->actingAs($user, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    // Seleciona só a empresa A, mas tenta injetar a filial B1 (da empresa B).
    // A filial B1 não está nas filiais acessíveis de A → intersecção vazia →
    // estreitamento descartado, e o escopo de empresa garante que B não vaza.
    Livewire::test(ExemploTable::class)
        ->set('filters.multi_select.empresa_id', [$a->id])
        ->set('filters.multi_select.filial_id', [$b1->id])
        ->assertSee($exemploA1->nome)
        ->assertDontSee($exemploB1->nome);
});
