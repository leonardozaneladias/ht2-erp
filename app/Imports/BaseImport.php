<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Base dos imports de planilha. `OnEachRow` (não `ToCollection`) de propósito:
 * `Row::getIndex()` entrega o número FÍSICO da linha no Excel em qualquer chunk —
 * a mesma régua que o vendor usa para numerar as falhas de validação. Um contador
 * próprio dessincroniza assim que a validação pula uma linha no meio (o usuário
 * procuraria o erro na linha errada da planilha).
 */
abstract class BaseImport implements OnEachRow, SkipsOnFailure, WithHeadingRow, WithValidation
{
    use SkipsFailures {
        onFailure as protected coletarFalhas;
    }

    protected int $linhasImportadas = 0;

    /** Linha FÍSICA do Excel (cabeçalho = linha 1) da linha em processamento. */
    protected int $numeroLinha = 0;

    public function onRow(Row $row): void
    {
        $this->numeroLinha = $row->getIndex();
        $this->aoProcessarLinha($this->numeroLinha);
        $this->importarLinha($row->toArray());
        $this->linhasImportadas++;
    }

    public function onFailure(Failure ...$failures): void
    {
        // Linha rejeitada pela validação também conta como processada (progresso).
        foreach ($failures as $failure) {
            $this->aoProcessarLinha($failure->row());
        }

        $this->coletarFalhas(...$failures);
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

    /** @return array<string, string> */
    public function customValidationAttributes(): array
    {
        return $this->atributosPorColuna();
    }

    /** @return list<array{linha: int, aba: string|null, campo: string, mensagem: string}> */
    public function getErros(): array
    {
        $erros = [];

        foreach ($this->failures() as $failure) {
            foreach ($failure->errors() as $message) {
                $erros[] = [
                    'linha' => $failure->row(),
                    'aba' => $this->tituloAba(),
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

    /** Título da aba (quando o import declara `WithTitle`) — anexado aos erros. */
    protected function tituloAba(): ?string
    {
        return $this instanceof WithTitle ? $this->title() : null;
    }

    /**
     * Gancho por linha processada — aceita OU rejeitada pela validação — com o
     * número físico da linha. No-op por padrão; as subclasses plugam contexto,
     * progresso etc.
     */
    protected function aoProcessarLinha(int $linha): void {}

    /**
     * Rótulos amigáveis por coluna (attribute da validação) — sem eles a mensagem
     * cita o cabeçalho cru ("O salario base centavos deve ser…"). No-op por padrão.
     *
     * @return array<string, string>
     */
    protected function atributosPorColuna(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    abstract protected function regrasPorColuna(): array;

    /** @return array<string, string> */
    abstract protected function mensagensValidacao(): array;

    /** @param array<string, mixed> $row */
    abstract protected function importarLinha(array $row): void;
}
