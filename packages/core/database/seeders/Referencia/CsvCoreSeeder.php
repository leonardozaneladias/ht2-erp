<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Seeders\Referencia;

use HT2ML\Core\Support\Referencia\CsvReferenceSeeder;

/**
 * Base dos seeders de catálogo do NÚCLEO: aponta o CSV para dentro do pacote.
 *
 * O CsvReferenceSeeder resolve o caminho em database_path(), que é a pasta do
 * app CONSUMIDOR — default correto para um seeder que o app escreve, e errado
 * para um que o pacote traz. Sem esta sobrescrita os catálogos do core só
 * semeiam quando o app por acaso tem os CSVs, que era o caso no monorepo e não
 * seria em nenhuma instalação real.
 *
 * Mesmo padrão do CsvFiscalSeeder na extensão fiscal.
 */
abstract class CsvCoreSeeder extends CsvReferenceSeeder
{
    protected function caminhoArquivo(): string
    {
        return __DIR__ . '/../../data/' . $this->arquivo();
    }
}
