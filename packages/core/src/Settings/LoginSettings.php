<?php

declare(strict_types=1);

namespace HT2ML\Core\Settings;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Aparência e textos da tela de login.
 */
final class LoginSettings extends Settings
{
    public string $fundo_imagem_arquivo;

    public string $titulo;

    public string $subtitulo;

    public bool $mostrar_logo;

    public bool $mostrar_versao;

    public static function group(): string
    {
        return SettingsGroup::LOGIN->value;
    }
}
