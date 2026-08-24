<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\AppearanceSettingsDTO;
use HT2ML\Core\Settings\AppearanceSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveAppearanceSettingsAction
{
    public function execute(AppearanceSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(AppearanceSettings::class);

            $settings->tema_padrao = $dto->tema_padrao;
            $settings->skin = $dto->skin;
            $settings->topbar_color = $dto->topbar_color;
            $settings->menu_color = $dto->menu_color;
            $settings->layout_width = $dto->layout_width;
            $settings->sidenav_size_padrao = $dto->sidenav_size_padrao;
            $settings->sidenav_user = $dto->sidenav_user;
            $settings->permitir_preferencia_usuario = $dto->permitir_preferencia_usuario;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['tema' => $dto->tema_padrao, 'skin' => $dto->skin])
                ->event('updated')
                ->log('Configurações de aparência atualizadas');
        });
    }
}
