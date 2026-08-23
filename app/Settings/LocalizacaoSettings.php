<?php

declare(strict_types=1);

namespace App\Settings;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Idioma, fuso horário, moeda e formatos aplicados em runtime.
 */
final class LocalizacaoSettings extends Settings
{
    public string $idioma;

    public string $timezone;

    public string $formato_data;

    public string $moeda_simbolo;

    public int $casas_decimais;

    public static function group(): string
    {
        return SettingsGroup::LOCALIZACAO->value;
    }
}
