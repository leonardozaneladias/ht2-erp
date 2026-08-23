<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\DTOs\Admin\Settings\ThemePresetDTO;
use App\Enums\Admin\Appearance\ThemePreset;

/**
 * Catálogo dos presets de tema. Cada preset combina cores acessíveis (texto
 * branco passa AA na primária) com skin e cores de chrome coerentes.
 */
final class ThemePresetCatalog
{
    public function paraEnum(ThemePreset $preset): ThemePresetDTO
    {
        return match ($preset) {
            ThemePreset::SAFIRA => new ThemePresetDTO(
                cor_primaria: '#1577ce',
                cor_secundaria: '#2b3a67',
                cor_sucesso: '#12b886',
                cor_warning: '#f5a623',
                cor_perigo: '#e5384b',
                cor_info: '#36a8ff',
                skin: 'default',
                topbar_color: 'light',
                menu_color: 'dark',
                tema_padrao: 'light',
            ),
            ThemePreset::GRAFITE => new ThemePresetDTO(
                cor_primaria: '#4f46e5',
                cor_secundaria: '#475569',
                cor_sucesso: '#22c55e',
                cor_warning: '#f59e0b',
                cor_perigo: '#ef4444',
                cor_info: '#38bdf8',
                skin: 'minimal',
                topbar_color: 'dark',
                menu_color: 'dark',
                tema_padrao: 'dark',
            ),
            ThemePreset::ESMERALDA => new ThemePresetDTO(
                cor_primaria: '#047857',
                cor_secundaria: '#0f766e',
                cor_sucesso: '#16a34a',
                cor_warning: '#d97706',
                cor_perigo: '#dc2626',
                cor_info: '#0891b2',
                skin: 'modern',
                topbar_color: 'light',
                menu_color: 'dark',
                tema_padrao: 'light',
            ),
            ThemePreset::VIOLETA => new ThemePresetDTO(
                cor_primaria: '#7c3aed',
                cor_secundaria: '#6d28d9',
                cor_sucesso: '#16a34a',
                cor_warning: '#f59e0b',
                cor_perigo: '#e11d48',
                cor_info: '#0ea5e9',
                skin: 'saas',
                topbar_color: 'light',
                menu_color: 'gradient',
                tema_padrao: 'light',
            ),
        };
    }

    /**
     * @return array<string, ThemePresetDTO> indexado pelo value do enum
     */
    public function todos(): array
    {
        $catalogo = [];

        foreach (ThemePreset::cases() as $preset) {
            $catalogo[$preset->value] = $this->paraEnum($preset);
        }

        return $catalogo;
    }
}
