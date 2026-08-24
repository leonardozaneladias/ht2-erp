<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\PowerGrid\Exports;

use PowerComponents\LivewirePowerGrid\Components\Exports\OpenSpout\v5\ExportToCsv as BaseExportToCsv;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

/**
 * Exporter CSV do PowerGrid com limpeza de HTML/whitespace (ver CleansExportData)
 * e separador ';' por padrão — convenção pt-BR (o Excel pt-BR usa ',' como
 * separador decimal, então ',' entre colunas quebra a planilha).
 */
class ExportToCsv extends BaseExportToCsv
{
    use CleansExportData;

    /**
     * @param  Exportable|array<string, mixed>  $exportOptions
     */
    public function build(Exportable|array $exportOptions): void
    {
        // O Livewire serializa o setUp, então aqui exportOptions pode chegar
        // como objeto Exportable ou como array. Cobrimos os dois casos.
        if ($exportOptions instanceof Exportable) {
            $exportOptions->csvSeparator(';');
        } else {
            $exportOptions['csvSeparator'] = ';';
        }

        parent::build($exportOptions);
    }
}
