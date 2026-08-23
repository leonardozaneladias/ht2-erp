<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Database\Seeders;

/**
 * CNAE 2.3 — subclasses (fonte IBGE/Concla). CSV:
 * `codigo,descricao,secao,divisao,grupo,classe`. Chave natural: codigo (7 díg).
 */
final class CnaeSeeder extends CsvFiscalSeeder
{
    protected function arquivo(): string
    {
        return 'cnaes.csv';
    }

    protected function tabela(): string
    {
        return 'cnaes';
    }

    protected function chaveNatural(): array
    {
        return ['codigo'];
    }

    protected function colunasUpdate(): array
    {
        return ['descricao', 'secao', 'divisao', 'grupo', 'classe'];
    }

    protected function cabecalhoEsperado(): ?array
    {
        return ['codigo', 'descricao', 'secao', 'divisao', 'grupo', 'classe'];
    }

    protected function minimoEsperado(): int
    {
        return 1300;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigo = $linha[0] ?? '';
        $descricao = $linha[1] ?? '';

        if (strlen($codigo) !== 7 || $descricao === '') {
            return null;
        }

        return [
            'codigo' => $codigo,
            'descricao' => $descricao,
            'secao' => ($linha[2] ?? '') !== '' ? $linha[2] : null,
            'divisao' => ($linha[3] ?? '') !== '' ? $linha[3] : null,
            'grupo' => ($linha[4] ?? '') !== '' ? $linha[4] : null,
            'classe' => ($linha[5] ?? '') !== '' ? $linha[5] : null,
        ];
    }
}
