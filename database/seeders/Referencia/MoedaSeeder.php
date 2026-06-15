<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * Moedas (ISO 4217). CSV: `codigo_iso,numerico,nome,simbolo,casas_decimais`.
 * `ativo` não é atualizado no re-seed (preserva a escolha do admin).
 */
final class MoedaSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'moedas.csv';
    }

    protected function tabela(): string
    {
        return 'moedas';
    }

    protected function chaveNatural(): array
    {
        return ['codigo_iso'];
    }

    protected function colunasUpdate(): array
    {
        return ['numerico', 'nome', 'simbolo', 'casas_decimais'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $iso = mb_strtoupper($linha[0] ?? '');

        if (strlen($iso) !== 3) {
            return null;
        }

        return [
            'codigo_iso' => $iso,
            'numerico' => ($linha[1] ?? '') !== '' ? $linha[1] : null,
            'nome' => $linha[2] ?? '',
            'simbolo' => ($linha[3] ?? '') !== '' ? $linha[3] : null,
            'casas_decimais' => (int) ($linha[4] ?? 2),
            'ativo' => true,
        ];
    }
}
