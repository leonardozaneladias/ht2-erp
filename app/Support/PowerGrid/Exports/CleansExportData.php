<?php

declare(strict_types=1);

namespace App\Support\PowerGrid\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Limpa os valores exportados pelo PowerGrid (XLS/CSV).
 *
 * Colunas com HTML (ex.: <x-shared.badge> para status, perfis) exportavam o
 * markup cru, com a indentação e as quebras de linha do template Blade. O
 * PowerGrid só aplica strip_tags (sem normalizar o whitespace), deixando algo
 * como "\n    \n    Ativa" na célula.
 *
 * Aqui forçamos strip_tags + Str::squish em cada valor, resultando em texto
 * limpo ("Ativa"). Vale para todos os componentes, sem precisar configurá-los.
 */
trait CleansExportData
{
    /**
     * @param  array<int, \PowerComponents\LivewirePowerGrid\Column>  $columns
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function prepare(Collection $data, array $columns, bool $stripTags): array
    {
        /** @var array{headers: array<int, string>, rows: array<int, array<int, mixed>>} $prepared */
        $prepared = parent::prepare($data, $columns, true);

        $prepared['rows'] = array_map(
            static fn (array $row): array => array_map(
                static fn (mixed $value): mixed => is_string($value) ? Str::squish($value) : $value,
                $row,
            ),
            $prepared['rows'] ?? [],
        );

        return $prepared;
    }
}
