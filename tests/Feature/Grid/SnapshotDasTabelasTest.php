<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Referencia\BancoTable;
use HT2ML\Core\Livewire\Admin\Referencia\CargoTable;
use HT2ML\Core\Livewire\Admin\Referencia\PaisTable;
use HT2ML\FiscalBr\Livewire\NcmTable;
use HT2ML\Rh\Livewire\Departamentos\DepartamentoTable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| A superfície das tabelas não muda ao migrar para a base declarativa
|--------------------------------------------------------------------------
|
| Os dez `*CrudTest` do repositório afirmam `assertOk()` e os verbos do CRUD.
| NENHUM deles afirma que uma coluna aparece, que um campo é pesquisável ou que
| um filtro é do tipo certo. Uma migração podia apagar a busca de um campo,
| trocar o widget de um filtro ou reordenar as colunas com a suíte verde.
|
| Aqui o snapshot é gravado ANTES de migrar e conferido depois. Se o arquivo
| .json mudar num PR de migração, a mudança é a revisão: ou foi intencional e
| se explica, ou é a regressão que este teste existe para pegar.
|
| Não é dataset por preguiça de escrever quatro testes: é porque o item de teste
| e o item de migração são o mesmo trabalho, e o repositório inteiro não tem um
| único dataset — os dez CrudTest são copy-paste, ~1.109 linhas duplicadas.
|
*/

dataset('tabelas migradas', [
    'bancos' => [BancoTable::class],
    'cargos' => [CargoTable::class],
    'paises' => [PaisTable::class],
    'ncms' => [NcmTable::class],
    // Multiempresa, com prefixo de módulo na permissão e ações próprias — é a
    // que prova o trait RecursoMultiEmpresa e o gancho modulo().
    'departamentos' => [DepartamentoTable::class],
]);

it('preserva colunas, filtros e cabeçalho de exportação', function (string $classe): void {
    $snapshot = snapshotDaTabela($classe);

    $arquivo = __DIR__ . '/snapshots/' . class_basename($classe) . '.json';
    $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (! file_exists($arquivo)) {
        file_put_contents($arquivo, $json . "\n");

        $this->markTestSkipped("Snapshot criado: {$arquivo}. Rode de novo para conferir.");
    }

    expect($snapshot)->toEqual(
        json_decode((string) file_get_contents($arquivo), true),
        "A superfície de {$classe} mudou. Se foi de propósito, apague {$arquivo} e regrave — o diff é a revisão.",
    );
})->with('tabelas migradas');
