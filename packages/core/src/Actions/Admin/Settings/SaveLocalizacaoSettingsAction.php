<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\LocalizacaoSettingsDTO;
use HT2ML\Core\Settings\LocalizacaoSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveLocalizacaoSettingsAction
{
    public function execute(LocalizacaoSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(LocalizacaoSettings::class);

            $settings->idioma = $dto->idioma;
            $settings->timezone = $dto->timezone;
            $settings->formato_data = $dto->formato_data;
            $settings->moeda_simbolo = $dto->moeda_simbolo;
            $settings->casas_decimais = $dto->casas_decimais;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['idioma' => $dto->idioma, 'timezone' => $dto->timezone])
                ->event('updated')
                ->log('Configurações de localização atualizadas');
        });
    }
}
