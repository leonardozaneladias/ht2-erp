<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Export;

use HT2ML\Core\DTOs\Admin\Export\ExportavelDTO;
use HT2ML\Core\Exports\TabelaExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportarTabelaExcelAction
{
    public function executeDownload(ExportavelDTO $dados): BinaryFileResponse
    {
        $nome = Str::slug($dados->titulo) . '.xlsx';

        return Excel::download(new TabelaExport($dados), $nome);
    }
}
