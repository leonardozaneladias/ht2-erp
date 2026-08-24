<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use HT2ML\Core\Actions\Admin\Export\ExportarTabelaExcelAction;
use HT2ML\Core\DTOs\Admin\Export\ExportavelDTO;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dá a um componente (ex.: tabela PowerGrid) export para Excel. O componente
 * só implementa dadosParaExportacao(); o trait cuida do download.
 *
 * @phpstan-ignore trait.unused
 */
trait ExportaExcel
{
    public function exportarExcel(ExportarTabelaExcelAction $action): BinaryFileResponse
    {
        return $action->executeDownload($this->dadosParaExportacao());
    }

    abstract protected function dadosParaExportacao(): ExportavelDTO;
}
