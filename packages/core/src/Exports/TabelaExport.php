<?php

declare(strict_types=1);

namespace HT2ML\Core\Exports;

use HT2ML\Core\DTOs\Admin\Export\ExportavelDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TabelaExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly ExportavelDTO $dados) {}

    /** @return list<list<string>> */
    public function array(): array
    {
        return $this->dados->linhas;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->dados->colunas;
    }

    public function title(): string
    {
        return mb_substr($this->dados->titulo, 0, 31);
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
