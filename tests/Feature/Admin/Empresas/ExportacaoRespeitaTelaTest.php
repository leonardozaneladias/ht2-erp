<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Empresas\EmpresasTable;
use HT2ML\Core\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * A exportação precisa exportar O QUE ESTÁ NA TELA.
 *
 * Até 2026-08-24 as quatro implementações de dadosParaExportacao() faziam
 * `$this->datasource()->get()` — a query crua, sem busca, sem filtro de coluna e
 * sem ordenação. O usuário filtrava para 12 linhas, clicava "Exportar PDF" e
 * recebia as 40.000. Não é lentidão: é resposta errada entregue a um humano, com
 * a tabela inteira carregada em memória de quebra.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('export-empresas@teste.com');
    $this->admin->assignRole('super-admin');
});

/** Invoca o dadosParaExportacao() protegido do componente sob teste. */
function exportacaoDe(object $componente): HT2ML\Core\DTOs\Admin\Export\ExportavelDTO
{
    $metodo = new ReflectionMethod($componente, 'dadosParaExportacao');

    return $metodo->invoke($componente);
}

it('exporta só as linhas que sobraram no filtro', function (): void {
    Empresa::create(['nome' => 'Alpha Servicos', 'cnpj' => '11222333000181', 'ativo' => true]);
    Empresa::create(['nome' => 'Beta Comercio', 'cnpj' => '99888777000166', 'ativo' => true]);
    Empresa::create(['nome' => 'Gama Industria', 'cnpj' => '44555666000122', 'ativo' => true]);

    $teste = Livewire::actingAs($this->admin, 'admin')
        ->test(EmpresasTable::class)
        ->set('filters', ['input_text' => ['nome' => 'Alpha']]);

    $dados = exportacaoDe($teste->instance());

    $nomes = array_column($dados->linhas, 0);

    expect($nomes)->toContain('Alpha Servicos')
        ->and($nomes)->not->toContain('Beta Comercio')
        ->and($nomes)->not->toContain('Gama Industria');
});

it('exporta tudo quando não há filtro', function (): void {
    Empresa::create(['nome' => 'Alpha Servicos', 'cnpj' => '11222333000181', 'ativo' => true]);
    Empresa::create(['nome' => 'Beta Comercio', 'cnpj' => '99888777000166', 'ativo' => true]);

    $teste = Livewire::actingAs($this->admin, 'admin')->test(EmpresasTable::class);

    $nomes = array_column(exportacaoDe($teste->instance())->linhas, 0);

    expect($nomes)->toContain('Alpha Servicos')->toContain('Beta Comercio');
});

it('respeita a busca global, não só os filtros de coluna', function (): void {
    Empresa::create(['nome' => 'Alpha Servicos', 'cnpj' => '11222333000181', 'ativo' => true]);
    Empresa::create(['nome' => 'Beta Comercio', 'cnpj' => '99888777000166', 'ativo' => true]);

    $teste = Livewire::actingAs($this->admin, 'admin')
        ->test(EmpresasTable::class)
        ->set('search', 'Beta');

    $nomes = array_column(exportacaoDe($teste->instance())->linhas, 0);

    expect($nomes)->toContain('Beta Comercio')
        ->and($nomes)->not->toContain('Alpha Servicos');
});
