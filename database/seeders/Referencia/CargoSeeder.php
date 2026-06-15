<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * Cargos (CBO). CSV: `codigo_cbo,descricao`. Conjunto inicial curado (a expandir);
 * o catálogo é editável no admin. `ativo` não é atualizado no re-seed.
 */
final class CargoSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'cargos.csv';
    }

    protected function tabela(): string
    {
        return 'cargos';
    }

    protected function chaveNatural(): array
    {
        return ['codigo_cbo'];
    }

    protected function colunasUpdate(): array
    {
        return ['descricao'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigo = $linha[0] ?? '';
        $descricao = $linha[1] ?? '';

        if (strlen($codigo) !== 6 || $descricao === '') {
            return null;
        }

        return [
            'codigo_cbo' => $codigo,
            'descricao' => $descricao,
            'ativo' => true,
        ];
    }
}
