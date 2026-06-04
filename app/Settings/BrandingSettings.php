<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Identidade visual do sistema.
 *
 * Os campos `*_arquivo` guardam caminhos relativos ao disco público
 * (storage/app/public). Quando vazios, o BrandingService usa o fallback de
 * config/branding.php. As cores são strings hex (#RRGGBB) injetadas como CSS
 * custom properties em runtime, sobrescrevendo o @theme do Tailwind.
 */
final class BrandingSettings extends Settings
{
    public string $logo_arquivo;

    public string $logo_dark_arquivo;

    public string $logo_sm_arquivo;

    public string $favicon_arquivo;

    public string $nome_sistema;

    public string $slogan;

    public string $cor_primaria;

    public string $cor_secundaria;

    public string $cor_sucesso;

    public string $cor_warning;

    public string $cor_perigo;

    public string $cor_info;

    public static function group(): string
    {
        return SettingsGroup::BRANDING->value;
    }
}
