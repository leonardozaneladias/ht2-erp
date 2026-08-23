<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Database\Seeders;

use App\Support\Referencia\CsvReferenceSeeder;

/**
 * Base dos seeders do pacote: aponta o CSV para dentro da extensão.
 *
 * O CsvReferenceSeeder do core resolve o caminho em database_path(), que é a
 * pasta do app. Um pacote traz seus próprios dados, então sobrescrever este
 * único método é tudo que separa "extensão instalada" de "extensão semeando
 * do diretório errado".
 */
abstract class CsvFiscalSeeder extends CsvReferenceSeeder
{
    protected function caminhoArquivo(): string
    {
        return __DIR__ . '/../data/' . $this->arquivo();
    }
}
