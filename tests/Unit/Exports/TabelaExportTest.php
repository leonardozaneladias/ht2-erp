<?php

declare(strict_types=1);

use App\DTOs\Admin\Export\ExportavelDTO;
use App\Exports\TabelaExport;

test('TabelaExport retorna headings corretos', function () {
    $dto = new ExportavelDTO('Clientes', ['Nome', 'Email'], [['João', 'joao@ex.com']]);
    $export = new TabelaExport($dto);
    expect($export->headings())->toBe(['Nome', 'Email']);
});

test('TabelaExport retorna linhas corretas', function () {
    $dto = new ExportavelDTO('Clientes', ['Nome'], [['João'], ['Maria']]);
    $export = new TabelaExport($dto);
    expect($export->array())->toBe([['João'], ['Maria']]);
});

test('TabelaExport trunca título longo a 31 chars', function () {
    $dto = new ExportavelDTO('Relatório Muito Longo de Clientes', [], []);
    $export = new TabelaExport($dto);
    expect(mb_strlen($export->title()))->toBeLessThanOrEqual(31);
});
