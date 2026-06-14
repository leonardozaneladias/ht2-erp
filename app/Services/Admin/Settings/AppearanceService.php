<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Enums\Admin\Appearance\LayoutWidth;
use App\Enums\Admin\Appearance\MenuColor;
use App\Enums\Admin\Appearance\SidenavSize;
use App\Enums\Admin\Appearance\Skin;
use App\Enums\Admin\Appearance\TemaPadrao;
use App\Enums\Admin\Appearance\TopbarColor;
use App\Settings\AppearanceSettings;

/**
 * Resolve os atributos de aparência/layout (data-*) para as views.
 *
 * Cada valor é validado contra o enum correspondente com fallback ao padrão —
 * defesa contra valores inválidos no banco que poderiam injetar um atributo
 * quebrado no <html>. Sem Request; lê apenas os settings da instância (override
 * de layout por empresa está fora de escopo).
 */
final class AppearanceService
{
    public function __construct(private readonly AppearanceSettings $settings) {}

    public function temaPadrao(): string
    {
        return (TemaPadrao::tryFrom($this->settings->tema_padrao) ?? TemaPadrao::padrao())->value;
    }

    public function skin(): string
    {
        return (Skin::tryFrom($this->settings->skin) ?? Skin::padrao())->value;
    }

    public function topbarColor(): string
    {
        return (TopbarColor::tryFrom($this->settings->topbar_color) ?? TopbarColor::padrao())->value;
    }

    public function menuColor(): string
    {
        return (MenuColor::tryFrom($this->settings->menu_color) ?? MenuColor::padrao())->value;
    }

    public function layoutWidth(): string
    {
        return (LayoutWidth::tryFrom($this->settings->layout_width) ?? LayoutWidth::padrao())->value;
    }

    public function sidenavSizePadrao(): string
    {
        return (SidenavSize::tryFrom($this->settings->sidenav_size_padrao) ?? SidenavSize::padrao())->value;
    }

    public function sidenavUser(): bool
    {
        return $this->settings->sidenav_user;
    }

    public function permitirPreferenciaUsuario(): bool
    {
        return $this->settings->permitir_preferencia_usuario;
    }

    /**
     * Config no formato consumido pelo theme-bootstrap (window.config / defaultConfig).
     * As chaves espelham o objeto JS: 'sidenav-color' = data-menu-color.
     *
     * @return array<string, string|bool>
     */
    public function paraThemeConfig(): array
    {
        return [
            'dir' => 'ltr',
            'skin' => $this->skin(),
            'theme' => $this->temaPadrao(),
            'width' => $this->layoutWidth(),
            'position' => 'fixed',
            'orientation' => 'vertical',
            'sidenav-size' => $this->sidenavSizePadrao(),
            'sidenav-user' => $this->sidenavUser(),
            'topbar-color' => $this->topbarColor(),
            'sidenav-color' => $this->menuColor(),
        ];
    }
}
