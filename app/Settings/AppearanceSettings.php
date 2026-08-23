<?php

declare(strict_types=1);

namespace App\Settings;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Aparência/layout da instância (atributos data-* do Inspinia no <html>).
 *
 * Complementa BrandingSettings (identidade: logos, cores, nome). Aqui ficam
 * tema padrão, skin, cores de topbar/menu, largura e tamanho do menu — antes
 * fixos no layout.blade.php e controláveis só via sessionStorage no cliente.
 */
final class AppearanceSettings extends Settings
{
    public string $tema_padrao;

    public string $skin;

    public string $topbar_color;

    public string $menu_color;

    public string $layout_width;

    public string $sidenav_size_padrao;

    public bool $sidenav_user;

    /** Se o usuário pode sobrescrever na sessão (dark mode, colapso do menu). */
    public bool $permitir_preferencia_usuario;

    public static function group(): string
    {
        return SettingsGroup::APARENCIA->value;
    }
}
