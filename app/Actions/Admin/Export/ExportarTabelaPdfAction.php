<?php

declare(strict_types=1);

namespace App\Actions\Admin\Export;

use App\DTOs\Admin\Export\ExportavelDTO;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Gera o PDF de uma tabela genérica (título + colunas + linhas). API-ready:
 * recebe DTO, devolve o objeto PDF (quem decide download/save/stream é o
 * chamador — componente, controller ou job).
 */
final class ExportarTabelaPdfAction
{
    public function execute(ExportavelDTO $dados): DomPdf
    {
        return Pdf::loadView('exports.tabela-pdf', ['dados' => $dados])
            ->setPaper('a4', 'landscape');
    }
}
