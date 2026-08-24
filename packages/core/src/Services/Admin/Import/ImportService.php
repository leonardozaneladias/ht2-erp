<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Import;

use HT2ML\Core\DTOs\Admin\ImportResultadoDTO;
use HT2ML\Core\Enums\StatusImport;
use HT2ML\Core\Imports\BaseImport;
use HT2ML\Core\Models\ImportLog;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ImportService
{
    public function importar(
        UploadedFile $arquivo,
        string $tipo,
        int $empresaId,
        BaseImport $import,
    ): ImportResultadoDTO {
        $log = ImportLog::withoutGlobalScope('empresa')->create([
            'empresa_id' => $empresaId,
            'tipo' => $tipo,
            'arquivo_original' => $arquivo->getClientOriginalName(),
            'total_linhas' => 0,
            'linhas_importadas' => 0,
            'linhas_com_erro' => 0,
            'status' => StatusImport::PROCESSANDO,
        ]);

        try {
            Excel::import($import, $arquivo);

            $erros = $import->getErros();
            $linhasImportadas = $import->getLinhasImportadas();
            $linhasComErro = count($erros);
            $totalLinhas = $linhasImportadas + $linhasComErro;

            $log->update([
                'total_linhas' => $totalLinhas,
                'linhas_importadas' => $linhasImportadas,
                'linhas_com_erro' => $linhasComErro,
                'status' => StatusImport::CONCLUIDO,
                'erros' => $erros ?: null,
            ]);

            return new ImportResultadoDTO(
                totalLinhas: $totalLinhas,
                linhasImportadas: $linhasImportadas,
                linhasComErro: $linhasComErro,
                erros: $erros,
            );
        } catch (Throwable $e) {
            $log->update([
                'status' => StatusImport::FALHOU,
                'erros' => [['linha' => 0, 'campo' => '', 'mensagem' => $e->getMessage()]],
            ]);

            throw $e;
        }
    }
}
