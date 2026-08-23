<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Appearance;

/**
 * Presets de tema curados, aplicáveis em 1 clique. Os valores ficam no
 * ThemePresetCatalog (DTOs); aqui só a identidade e os rótulos.
 */
enum ThemePreset: string
{
    public function label(): string
    {
        return match ($this) {
            self::SAFIRA => 'Safira',
            self::GRAFITE => 'Grafite',
            self::ESMERALDA => 'Esmeralda',
            self::VIOLETA => 'Violeta',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::SAFIRA => 'Azul clássico, claro. Padrão de fábrica.',
            self::GRAFITE => 'Escuro e sóbrio, com acento índigo.',
            self::ESMERALDA => 'Verde, claro e fresco.',
            self::VIOLETA => 'Violeta moderno, claro.',
        };
    }

    /** Cor representativa para o cartão do preset no seletor. */
    public function corAmostra(): string
    {
        return match ($this) {
            self::SAFIRA => '#1577ce',
            self::GRAFITE => '#4f46e5',
            self::ESMERALDA => '#047857',
            self::VIOLETA => '#7c3aed',
        };
    }

    public static function padrao(): self
    {
        return self::SAFIRA;
    }
    case SAFIRA = 'safira';
    case GRAFITE = 'grafite';
    case ESMERALDA = 'esmeralda';
    case VIOLETA = 'violeta';
}
