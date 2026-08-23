<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Settings;

use HT2ML\Core\Enums\Admin\Appearance\LayoutWidth;
use HT2ML\Core\Enums\Admin\Appearance\MenuColor;
use HT2ML\Core\Enums\Admin\Appearance\SidenavSize;
use HT2ML\Core\Enums\Admin\Appearance\Skin;
use HT2ML\Core\Enums\Admin\Appearance\TemaPadrao;
use HT2ML\Core\Enums\Admin\Appearance\TopbarColor;
use HT2ML\Core\Settings\AppearanceSettings;
use Throwable;

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
        return (TemaPadrao::tryFrom($this->texto('tema_padrao')) ?? TemaPadrao::padrao())->value;
    }

    public function skin(): string
    {
        return (Skin::tryFrom($this->texto('skin')) ?? Skin::padrao())->value;
    }

    public function topbarColor(): string
    {
        return (TopbarColor::tryFrom($this->texto('topbar_color')) ?? TopbarColor::padrao())->value;
    }

    public function menuColor(): string
    {
        return (MenuColor::tryFrom($this->texto('menu_color')) ?? MenuColor::padrao())->value;
    }

    public function layoutWidth(): string
    {
        return (LayoutWidth::tryFrom($this->texto('layout_width')) ?? LayoutWidth::padrao())->value;
    }

    public function sidenavSizePadrao(): string
    {
        return (SidenavSize::tryFrom($this->texto('sidenav_size_padrao')) ?? SidenavSize::padrao())->value;
    }

    public function sidenavUser(): bool
    {
        return $this->booleano('sidenav_user', true);
    }

    public function permitirPreferenciaUsuario(): bool
    {
        return $this->booleano('permitir_preferencia_usuario', true);
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

    /**
     * Lê uma propriedade string dos settings com tolerância a falhas (sem banco,
     * tabela settings ausente etc.) — assim o tema funciona em páginas de erro
     * e durante a instalação. Retorna '' no erro (cai no padrão via enum).
     */
    private function texto(string $propriedade): string
    {
        try {
            $valor = $this->settings->{$propriedade};

            return is_string($valor) ? $valor : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function booleano(string $propriedade, bool $padrao): bool
    {
        try {
            return (bool) $this->settings->{$propriedade};
        } catch (Throwable) {
            return $padrao;
        }
    }
}
