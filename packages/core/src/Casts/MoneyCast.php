<?php

declare(strict_types=1);

namespace HT2ML\Core\Casts;

use HT2ML\Core\Support\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cast Eloquent: converte coluna INTEGER (centavos) <-> Money VO.
 *
 * @implements CastsAttributes<Money, int>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * Converte o valor bruto do banco (int centavos) para o Money VO.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromCentavos((int) $value);
    }

    /**
     * Converte Money VO (ou int centavos) para int a ser gravado no banco.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof Money) {
            return $value->centavos();
        }

        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException("Valor monetário não pode ser negativo: {$value}");
            }

            return $value;
        }

        throw new InvalidArgumentException(
            'MoneyCast espera Money|int, recebeu: ' . get_debug_type($value),
        );
    }
}
