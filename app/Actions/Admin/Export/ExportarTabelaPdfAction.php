<?php

declare(strict_types=1);

namespace App\Actions\Admin\Export;

use App\DTOs\Admin\Export\ExportavelDTO;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use HT2ML\Core\Services\Admin\Settings\BrandingService;

/**
 * Gera o PDF de uma tabela genérica (título + colunas + linhas). API-ready:
 * recebe DTO, devolve o objeto PDF (quem decide download/save/stream é o
 * chamador — componente, controller ou job).
 */
final class ExportarTabelaPdfAction
{
    public function __construct(private readonly BrandingService $branding) {}

    public function execute(ExportavelDTO $dados): DomPdf
    {
        $dadosComLogo = new ExportavelDTO(
            titulo: $dados->titulo,
            colunas: $dados->colunas,
            linhas: $dados->linhas,
            logoUrl: $this->branding->logoUrl('light'),
        );

        return Pdf::loadView('exports.tabela-pdf', ['dados' => $dadosComLogo])
            ->setPaper('a4', 'landscape');
    }
}
