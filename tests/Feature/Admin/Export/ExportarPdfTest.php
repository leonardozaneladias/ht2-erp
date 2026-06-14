<?php

declare(strict_types=1);

use App\Actions\Admin\Export\ExportarTabelaPdfAction;
use App\DTOs\Admin\Export\ExportavelDTO;
use App\Jobs\GerarExportacaoPdfJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('gera um PDF não-vazio a partir do DTO', function () {
    $dados = new ExportavelDTO('Relatório de teste', ['Nome', 'Status'], [
        ['Alpha', 'Ativa'],
        ['Beta', 'Inativa'],
    ]);

    $pdf = app(ExportarTabelaPdfAction::class)->execute($dados);

    expect($pdf->output())->toStartWith('%PDF');
});

it('enfileira a exportação na fila exports', function () {
    Queue::fake();

    GerarExportacaoPdfJob::dispatch(new ExportavelDTO('X', ['A'], [['1']]), 1);

    Queue::assertPushedOn('exports', GerarExportacaoPdfJob::class);
});

it('o job gera e armazena o PDF no disco', function () {
    Storage::fake('local');

    (new GerarExportacaoPdfJob(new ExportavelDTO('Empresas', ['Nome'], [['Alpha']]), 1))
        ->handle(app(ExportarTabelaPdfAction::class));

    expect(Storage::disk('local')->allFiles('exports'))->toHaveCount(1);
});
