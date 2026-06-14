<?php

declare(strict_types=1);

namespace App\Enums\Admin\Appearance;

/**
 * Presets de tema curados, aplicáveis em 1 clique. Os valores ficam no
 * ThemePresetCatalog (DTOs); aqui só a identidade e os rótulos.
 */
enum ThemePreset: string
{
    public function label(): string
    {
        return match ($this) {
            self::HT2_ERP => 'HT2 ERP',
            self::GRAFITE => 'Grafite',
            self::ESMERALDA => 'Esmeralda',
            self::VIOLETA => 'Violeta',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::HT2_ERP => 'Identidade padrão — azul HT2, claro.',
            self::GRAFITE => 'Escuro e sóbrio, com acento índigo.',
            self::ESMERALDA => 'Verde, claro e fresco.',
            self::VIOLETA => 'Violeta moderno, claro.',
        };
    }

    /** Cor representativa para o cartão do preset no seletor. */
    public function corAmostra(): string
    {
        return match ($this) {
            self::HT2_ERP => '#1577ce',
            self::GRAFITE => '#4f46e5',
            self::ESMERALDA => '#047857',
            self::VIOLETA => '#7c3aed',
        };
    }

    public static function padrao(): self
    {
        return self::HT2_ERP;
    }
    case HT2_ERP = 'ht2_erp';
    case GRAFITE = 'grafite';
    case ESMERALDA = 'esmeralda';
    case VIOLETA = 'violeta';
}
