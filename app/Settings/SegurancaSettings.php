<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Políticas de segurança da instalação: senha, sessão, lockout, alertas e 2FA.
 *
 * `sessao_timeout_minutos` é aplicado a config('session.lifetime') pelo
 * SettingsRuntimeApplier. `exigir_2fa_admin` é imposto pelo middleware
 * EnsureTwoFactorEnabled. `permitir_2fa_email` é o switch global do segundo
 * fator por e-mail — pré-requisito para a preferência individual de cada usuário.
 */
final class SegurancaSettings extends Settings
{
    public int $senha_min_caracteres;

    public bool $senha_exige_maiuscula;

    public bool $senha_exige_numero;

    public bool $senha_exige_especial;

    public int $sessao_timeout_minutos;

    public bool $exigir_2fa_admin;

    public bool $permitir_2fa_email;

    public int $dias_retencao_logs;

    public int $impersonation_timeout_minutos;

    public int $login_max_tentativas;

    public int $login_janela_minutos;

    public int $lockout_max_falhas;

    public int $lockout_duracao_minutos;

    public bool $alertas_seguranca_habilitados;

    public bool $alerta_login_super_admin;

    public static function group(): string
    {
        return SettingsGroup::SEGURANCA->value;
    }
}
