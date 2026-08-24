<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Seeders\Referencia;

/**
 * Moedas (ISO 4217). CSV: `codigo_iso,numerico,nome,simbolo,casas_decimais`.
 * `ativo` não é atualizado no re-seed (preserva a escolha do admin).
 */
final class MoedaSeeder extends CsvCoreSeeder
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

    protected function cabecalhoEsperado(): ?array
    {
        return ['codigo_iso', 'numerico', 'nome', 'simbolo', 'casas_decimais'];
    }

    protected function minimoEsperado(): int
    {
        return 30;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $iso = mb_strtoupper($linha[0] ?? '');

        if (strlen($iso) !== 3) {
            return null;
        }

        $casas = $linha[4] ?? '';

        return [
            'codigo_iso' => $iso,
            'numerico' => ($linha[1] ?? '') !== '' ? $linha[1] : null,
            'nome' => $linha[2] ?? '',
            'simbolo' => ($linha[3] ?? '') !== '' ? $linha[3] : null,
            'casas_decimais' => is_numeric($casas) ? (int) $casas : 2,
            'ativo' => true,
        ];
    }
}
