<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Admin\Export\ExportarTabelaPdfAction;
use App\DTOs\Admin\Export\ExportavelDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gera um PDF de exportação em background (fila `exports`) e o guarda no disco.
 * Exemplo de Job do boilerplate: trabalho pesado fora do request, com trilha de
 * auditoria. Use para exports grandes (a versão síncrona fica no componente).
 */
final class GerarExportacaoPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ExportavelDTO $dados,
        private readonly int $adminUserId,
    ) {
        $this->onQueue('exports');
    }

    public function handle(ExportarTabelaPdfAction $action): void
    {
        $pdf = $action->execute($this->dados);

        $caminho = 'exports/' . now()->format('Ymd_His') . '_' . Str::slug($this->dados->titulo) . '.pdf';
        Storage::disk('local')->put($caminho, $pdf->output());

        activity('exportacoes')
            ->event('pdf_gerado')
            ->withProperties([
                'arquivo' => $caminho,
                'admin_user_id' => $this->adminUserId,
                'registros' => count($this->dados->linhas),
            ])
            ->log('Exportação PDF gerada');
    }
}
