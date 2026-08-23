<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\DTOs\Admin\Settings\SegurancaSettingsDTO;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveSegurancaSettingsAction
{
    public function execute(SegurancaSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(SegurancaSettings::class);

            $settings->senha_min_caracteres = $dto->senha_min_caracteres;
            $settings->senha_exige_maiuscula = $dto->senha_exige_maiuscula;
            $settings->senha_exige_numero = $dto->senha_exige_numero;
            $settings->senha_exige_especial = $dto->senha_exige_especial;
            $settings->sessao_timeout_minutos = $dto->sessao_timeout_minutos;
            $settings->exigir_2fa_admin = $dto->exigir_2fa_admin;
            $settings->permitir_2fa_email = $dto->permitir_2fa_email;
            $settings->dias_retencao_logs = $dto->dias_retencao_logs;
            $settings->login_max_tentativas = $dto->login_max_tentativas;
            $settings->login_janela_minutos = $dto->login_janela_minutos;
            $settings->lockout_max_falhas = $dto->lockout_max_falhas;
            $settings->lockout_duracao_minutos = $dto->lockout_duracao_minutos;
            $settings->alertas_seguranca_habilitados = $dto->alertas_seguranca_habilitados;
            $settings->alerta_login_super_admin = $dto->alerta_login_super_admin;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->event('updated')
                ->log('Configurações de segurança atualizadas');
        });
    }
}
