<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Referencia;

use ValueError;

/**
 * Região do Brasil. Derivável do 1º dígito do código IBGE do estado
 * (1=Norte, 2=Nordeste, 3=Sudeste, 4=Sul, 5=Centro-Oeste).
 */
enum RegiaoBrasil: string
{
    public function label(): string
    {
        return match ($this) {
            self::Norte => 'Norte',
            self::Nordeste => 'Nordeste',
            self::Sudeste => 'Sudeste',
            self::Sul => 'Sul',
            self::CentroOeste => 'Centro-Oeste',
        };
    }

    /** Região pelo código IBGE do estado (2 dígitos): o 1º dígito determina a região. */
    public static function peloCodigoIbge(string $codigoIbge): self
    {
        return match ($codigoIbge[0] ?? '') {
            '1' => self::Norte,
            '2' => self::Nordeste,
            '3' => self::Sudeste,
            '4' => self::Sul,
            '5' => self::CentroOeste,
            default => throw new ValueError("Código IBGE de estado inválido: {$codigoIbge}"),
        };
    }
    case Norte = 'norte';
    case Nordeste = 'nordeste';
    case Sudeste = 'sudeste';
    case Sul = 'sul';
    case CentroOeste = 'centro-oeste';
}
