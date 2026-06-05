<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Políticas de segurança da instalação.
 *
 * A implementação efetiva do 2FA pertence ao Épico 2; aqui mantemos apenas a
 * flag `exigir_2fa_admin`. `sessao_timeout_minutos` é aplicado a
 * config('session.lifetime') pelo SettingsRuntimeApplier.
 */
final class SegurancaSettings extends Settings
{
    public int $senha_min_caracteres;

    public bool $senha_exige_maiuscula;

    public bool $senha_exige_numero;

    public bool $senha_exige_especial;

    public int $sessao_timeout_minutos;

    public bool $exigir_2fa_admin;

    public int $dias_retencao_logs;

    public int $impersonation_timeout_minutos;

    public static function group(): string
    {
        return SettingsGroup::SEGURANCA->value;
    }
}
