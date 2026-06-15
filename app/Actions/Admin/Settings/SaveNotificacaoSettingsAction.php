<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\DTOs\Admin\Settings\NotificacaoSettingsDTO;
use App\Settings\NotificacaoSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveNotificacaoSettingsAction
{
    public function execute(NotificacaoSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(NotificacaoSettings::class);

            $settings->toast_posicao = $dto->toast_posicao;
            $settings->toast_duracao = $dto->toast_duracao;
            $settings->toast_estilo = $dto->toast_estilo;
            $settings->toast_maximo = $dto->toast_maximo;
            $settings->confirmacao_posicao = $dto->confirmacao_posicao;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['posicao' => $dto->toast_posicao, 'estilo' => $dto->toast_estilo])
                ->event('updated')
                ->log('Configurações de notificações atualizadas');
        });
    }
}
