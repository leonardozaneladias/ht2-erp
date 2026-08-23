<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use App\Enums\Referencia\RegiaoBrasil;
use App\Support\Referencia\CsvReferenceSeeder;

/**
 * Estados (27 UF). CSV: `codigo_ibge,sigla,nome`. A região é derivada do 1º
 * dígito do código IBGE (não vem no CSV).
 */
final class EstadoSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'estados.csv';
    }

    protected function tabela(): string
    {
        return 'estados';
    }

    protected function chaveNatural(): array
    {
        return ['codigo_ibge'];
    }

    protected function colunasUpdate(): array
    {
        return ['sigla', 'nome', 'regiao'];
    }

    protected function cabecalhoEsperado(): ?array
    {
        return ['codigo_ibge', 'sigla', 'nome'];
    }

    protected function minimoEsperado(): int
    {
        return 27;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigoIbge = $linha[0] ?? '';
        $sigla = $linha[1] ?? '';
        $nome = $linha[2] ?? '';

        if (strlen($codigoIbge) !== 2 || $nome === '') {
            return null;
        }

        return [
            'codigo_ibge' => $codigoIbge,
            'sigla' => mb_strtoupper($sigla),
            'nome' => $nome,
            'regiao' => RegiaoBrasil::peloCodigoIbge($codigoIbge)->value,
        ];
    }
}
