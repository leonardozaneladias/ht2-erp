<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\BrandingSettingsDTO;
use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveBrandingSettingsAction
{
    public function execute(BrandingSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(BrandingSettings::class);

            $settings->nome_sistema = $dto->nome_sistema;
            $settings->slogan = $dto->slogan;
            $settings->cor_primaria = $dto->cor_primaria;
            $settings->cor_secundaria = $dto->cor_secundaria;
            $settings->cor_sucesso = $dto->cor_sucesso;
            $settings->cor_warning = $dto->cor_warning;
            $settings->cor_perigo = $dto->cor_perigo;
            $settings->cor_info = $dto->cor_info;

            // Caminhos null = nenhum upload novo; preserva o arquivo atual.
            if ($dto->logo_arquivo !== null) {
                $settings->logo_arquivo = $dto->logo_arquivo;
            }
            if ($dto->logo_dark_arquivo !== null) {
                $settings->logo_dark_arquivo = $dto->logo_dark_arquivo;
            }
            if ($dto->logo_sm_arquivo !== null) {
                $settings->logo_sm_arquivo = $dto->logo_sm_arquivo;
            }
            if ($dto->favicon_arquivo !== null) {
                $settings->favicon_arquivo = $dto->favicon_arquivo;
            }

            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'nome_sistema' => $dto->nome_sistema,
                    'cor_primaria' => $dto->cor_primaria,
                ])
                ->event('updated')
                ->log('Marca e tema atualizados');
        });
    }
}
