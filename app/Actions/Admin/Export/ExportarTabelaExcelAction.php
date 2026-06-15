<?php

declare(strict_types=1);

namespace App\Actions\Admin\Export;

use App\DTOs\Admin\Export\ExportavelDTO;
use App\Exports\TabelaExport;
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
