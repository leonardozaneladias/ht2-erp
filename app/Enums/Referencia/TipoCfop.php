<?php

declare(strict_types=1);

namespace App\Enums\Referencia;

use ValueError;

/**
 * Tipo de operação do CFOP. Derivável do 1º dígito do código:
 * 1/2/3 = entrada, 5/6/7 = saída.
 */
enum TipoCfop: string
{
    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    /** Tipo pelo 1º dígito do CFOP (4 dígitos). */
    public static function peloCodigo(string $codigo): self
    {
        return match ($codigo[0] ?? '') {
            '1', '2', '3' => self::Entrada,
            '5', '6', '7' => self::Saida,
            default => throw new ValueError("CFOP inválido: {$codigo}"),
        };
    }
    case Entrada = 'entrada';
    case Saida = 'saida';
}
