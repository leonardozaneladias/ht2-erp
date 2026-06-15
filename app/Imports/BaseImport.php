<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class BaseImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected int $linhasImportadas = 0;

    /** @param Collection<int, Collection<string, mixed>> $rows */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->importarLinha($row->toArray());
            $this->linhasImportadas++;
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->regrasPorColuna();
    }

    /** @return array<string, string> */
    public function customValidationMessages(): array
    {
        return $this->mensagensValidacao();
    }

    /** @return list<array{linha: int, campo: string, mensagem: string}> */
    public function getErros(): array
    {
        $erros = [];

        foreach ($this->failures() as $failure) {
            foreach ($failure->errors() as $message) {
                $erros[] = [
                    'linha' => $failure->row(),
                    'campo' => $failure->attribute(),
                    'mensagem' => $message,
                ];
            }
        }

        return $erros;
    }

    public function getLinhasImportadas(): int
    {
        return $this->linhasImportadas;
    }

    /** @return array<string, mixed> */
    abstract protected function regrasPorColuna(): array;

    /** @return array<string, string> */
    abstract protected function mensagensValidacao(): array;

    /** @param array<string, mixed> $row */
    abstract protected function importarLinha(array $row): void;
}
