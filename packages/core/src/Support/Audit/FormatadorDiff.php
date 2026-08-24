<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Audit;

use HT2ML\Core\Models\Activity;

/**
 * Converte o attribute_changes de uma Activity (attributes/old) em linhas
 * campo → (antes, depois) formatadas para exibição. Reusado pelo drawer de
 * detalhes da auditoria e pela timeline de histórico por registro.
 */
final class FormatadorDiff
{
    /**
     * @return list<array{campo: string, antes: ?string, depois: ?string}>
     */
    public static function linhas(?Activity $activity): array
    {
        $changes = $activity?->attribute_changes;

        if ($changes === null) {
            return [];
        }

        $depois = (array) data_get($changes, 'attributes', []);
        $antes = (array) data_get($changes, 'old', []);
        $campos = array_values(array_unique([...array_keys($antes), ...array_keys($depois)]));

        return array_map(static fn (string $campo): array => [
            'campo' => $campo,
            'antes' => array_key_exists($campo, $antes) ? self::formatar($antes[$campo]) : null,
            'depois' => array_key_exists($campo, $depois) ? self::formatar($depois[$campo]) : null,
        ], $campos);
    }

    private static function formatar(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '' => '—',
            is_bool($valor) => $valor ? 'Sim' : 'Não',
            is_array($valor) => (string) json_encode($valor, JSON_UNESCAPED_UNICODE),
            default => (string) $valor,
        };
    }
}
