<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use App\Support\Referencia\CsvReferenceSeeder;

/**
 * Tipos de logradouro. CSV: `nome,codigo,abrev`. Chave natural: nome.
 */
final class TipoLogradouroSeeder extends CsvReferenceSeeder
{
    protected function arquivo(): string
    {
        return 'tipos_logradouro.csv';
    }

    protected function tabela(): string
    {
        return 'tipos_logradouro';
    }

    protected function chaveNatural(): array
    {
        return ['nome'];
    }

    protected function colunasUpdate(): array
    {
        return ['codigo', 'abrev'];
    }

    protected function cabecalhoEsperado(): ?array
    {
        return ['nome', 'codigo', 'abrev'];
    }

    protected function minimoEsperado(): int
    {
        return 25;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $nome = $linha[0] ?? '';

        if ($nome === '') {
            return null;
        }

        return [
            'nome' => $nome,
            'codigo' => ($linha[1] ?? '') !== '' ? $linha[1] : null,
            'abrev' => ($linha[2] ?? '') !== '' ? $linha[2] : null,
            'ativo' => true,
        ];
    }
}
