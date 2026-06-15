<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;

/**
 * Bancos (participantes do SPB, fonte BrasilAPI/BACEN). CSV:
 * `ispb,codigo_compe,nome,nome_completo`. Chave natural: ISPB (8 díg).
 */
final class BancoSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'bancos.csv';
    }

    protected function tabela(): string
    {
        return 'bancos';
    }

    protected function chaveNatural(): array
    {
        return ['ispb'];
    }

    protected function colunasUpdate(): array
    {
        return ['codigo_compe', 'nome', 'nome_completo'];
    }

    protected function mapearLinha(array $linha): ?array
    {
        $ispb = $linha[0] ?? '';
        $nome = $linha[2] ?? '';

        if (strlen($ispb) !== 8 || $nome === '') {
            return null;
        }

        return [
            'ispb' => $ispb,
            'codigo_compe' => ($linha[1] ?? '') !== '' ? $linha[1] : null,
            'nome' => $nome,
            'nome_completo' => ($linha[3] ?? '') !== '' ? $linha[3] : null,
            'ativo' => true,
        ];
    }
}
