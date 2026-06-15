<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * Países (ISO 3166-1, fonte IBGE). CSV: `codigo_iso2,codigo_iso3,codigo_numerico,nome`.
 * `ativo` não é atualizado no re-seed (preserva a escolha do admin).
 */
final class PaisSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'paises.csv';
    }

    protected function tabela(): string
    {
        return 'paises';
    }

    protected function chaveNatural(): array
    {
        return ['codigo_iso2'];
    }

    protected function colunasUpdate(): array
    {
        return ['codigo_iso3', 'codigo_numerico', 'nome'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $iso2 = mb_strtoupper($linha[0] ?? '');

        if (strlen($iso2) !== 2) {
            return null;
        }

        return [
            'codigo_iso2' => $iso2,
            'codigo_iso3' => ($linha[1] ?? '') !== '' ? mb_strtoupper($linha[1]) : null,
            'codigo_numerico' => ($linha[2] ?? '') !== '' ? $linha[2] : null,
            'nome' => $linha[3] ?? '',
            'ativo' => true,
        ];
    }
}
