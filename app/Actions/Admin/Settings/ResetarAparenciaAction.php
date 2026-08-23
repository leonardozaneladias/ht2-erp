<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use HT2ML\Core\Enums\Admin\Appearance\ThemePreset;
use HT2ML\Core\Services\Admin\Settings\ThemePresetCatalog;
use HT2ML\Core\Settings\AppearanceSettings;
use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Restaura identidade e aparência ao padrão de fábrica (preset Safira):
 * limpa logos/favicon enviados (volta ao fallback), reaplica a paleta e o
 * layout padrão. Não toca em e-mail, segurança, localização ou empresa.
 */
final class ResetarAparenciaAction
{
    public function __construct(private readonly ThemePresetCatalog $catalog) {}

    public function execute(): void
    {
        DB::transaction(function (): void {
            $preset = $this->catalog->paraEnum(ThemePreset::SAFIRA);

            $branding = app(BrandingSettings::class);
            $branding->nome_sistema = (string) config('app.name', 'Admin');
            $branding->slogan = '';
            $branding->logo_arquivo = '';
            $branding->logo_dark_arquivo = '';
            $branding->logo_sm_arquivo = '';
            $branding->favicon_arquivo = '';
            $branding->cor_primaria = $preset->cor_primaria;
            $branding->cor_secundaria = $preset->cor_secundaria;
            $branding->cor_sucesso = $preset->cor_sucesso;
            $branding->cor_warning = $preset->cor_warning;
            $branding->cor_perigo = $preset->cor_perigo;
            $branding->cor_info = $preset->cor_info;
            $branding->save();

            $appearance = app(AppearanceSettings::class);
            $appearance->tema_padrao = $preset->tema_padrao;
            $appearance->skin = $preset->skin;
            $appearance->topbar_color = $preset->topbar_color;
            $appearance->menu_color = $preset->menu_color;
            $appearance->layout_width = 'fluid';
            $appearance->sidenav_size_padrao = 'default';
            $appearance->sidenav_user = true;
            $appearance->permitir_preferencia_usuario = true;
            $appearance->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->event('reset')
                ->log('Identidade e aparência restauradas ao padrão de fábrica');
        });
    }
}
