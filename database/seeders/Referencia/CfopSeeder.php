<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use App\Enums\Referencia\TipoCfop;
use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * CFOP. CSV: `codigo,descricao,aplicacao`. O `tipo` (entrada/saída) é derivado do
 * 1º dígito do código. Chave natural: codigo (4 díg).
 */
final class CfopSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'cfops.csv';
    }

    protected function tabela(): string
    {
        return 'cfops';
    }

    protected function chaveNatural(): array
    {
        return ['codigo'];
    }

    protected function colunasUpdate(): array
    {
        return ['descricao', 'tipo', 'aplicacao'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigo = $linha[0] ?? '';
        $descricao = $linha[1] ?? '';

        if (strlen($codigo) !== 4 || $descricao === '') {
            return null;
        }

        return [
            'codigo' => $codigo,
            'descricao' => $descricao,
            'tipo' => TipoCfop::peloCodigo($codigo)->value,
            'aplicacao' => ($linha[2] ?? '') !== '' ? $linha[2] : null,
        ];
    }
}
