<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * NCM (fonte Siscomex via BrasilAPI), só códigos de 8 dígitos ativos. CSV:
 * `codigo,descricao`. Chave natural: codigo (8 díg).
 */
final class NcmSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'ncms.csv';
    }

    protected function tabela(): string
    {
        return 'ncms';
    }

    protected function chaveNatural(): array
    {
        return ['codigo'];
    }

    protected function colunasUpdate(): array
    {
        return ['descricao'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigo = $linha[0] ?? '';
        $descricao = $linha[1] ?? '';

        if (strlen($codigo) !== 8 || $descricao === '') {
            return null;
        }

        return [
            'codigo' => $codigo,
            'descricao' => $descricao,
        ];
    }
}
