<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Actions\Admin\Export\ExportarTabelaPdfAction;
use App\DTOs\Admin\Export\ExportavelDTO;
use App\Jobs\GerarExportacaoPdfJob;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dá a um componente (ex.: tabela PowerGrid) export para PDF. O componente só
 * implementa dadosParaExportacao(); o trait cuida do download síncrono e do
 * enfileiramento assíncrono (fila `exports`) para volumes grandes.
 *
 * @mixin \Livewire\Component
 */
trait ExportaPdf
{
    public function exportarPdf(ExportarTabelaPdfAction $action): StreamedResponse
    {
        $dados = $this->dadosParaExportacao();
        $pdf = $action->execute($dados);

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            Str::slug($dados->titulo) . '.pdf',
        );
    }

    public function exportarPdfAssincrono(): void
    {
        GerarExportacaoPdfJob::dispatch($this->dadosParaExportacao(), (int) (auth('admin')->id() ?? 0));

        $this->dispatch('toast', variant: 'success', message: 'Exportação enfileirada. Avisaremos quando estiver pronta.');
    }

    abstract protected function dadosParaExportacao(): ExportavelDTO;
}
