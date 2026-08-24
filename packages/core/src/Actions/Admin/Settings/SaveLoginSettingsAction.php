<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\LoginSettingsDTO;
use HT2ML\Core\Settings\LoginSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveLoginSettingsAction
{
    public function execute(LoginSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(LoginSettings::class);

            $settings->titulo = $dto->titulo;
            $settings->subtitulo = $dto->subtitulo;
            $settings->mostrar_logo = $dto->mostrar_logo;
            $settings->mostrar_versao = $dto->mostrar_versao;

            if ($dto->fundo_imagem_arquivo !== null) {
                $settings->fundo_imagem_arquivo = $dto->fundo_imagem_arquivo;
            }

            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['titulo' => $dto->titulo])
                ->event('updated')
                ->log('Tela de login atualizada');
        });
    }
}
