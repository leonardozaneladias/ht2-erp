<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Configuração do servidor SMTP usado pela aplicação.
 *
 * Credenciais são persistidas criptografadas (ver encrypted()). Quando
 * `habilitar` é true, o SettingsRuntimeApplier sobrescreve o mailer padrão
 * em config('mail.*') no boot da aplicação.
 */
final class EmailSettings extends Settings
{
    public string $smtp_host;

    public int $smtp_port;

    public string $smtp_username;

    public string $smtp_password;

    /** tls | ssl | null */
    public string $criptografia;

    public string $from_email;

    public string $from_name;

    public bool $habilitar;

    public static function group(): string
    {
        return SettingsGroup::EMAIL->value;
    }

    /**
     * Propriedades persistidas criptografadas no banco.
     *
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['smtp_username', 'smtp_password'];
    }
}
