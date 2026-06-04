<?php

declare(strict_types=1);

namespace App\Enums\Admin;

/**
 * Grupos de configuração do sistema.
 *
 * O `value` casa com o `group` das classes em App\Settings e com a tabela
 * `settings` (coluna group). A ordem dos cases define a ordem das abas.
 */
enum SettingsGroup: string
{
    public function label(): string
    {
        return match ($this) {
            self::GERAL => 'Empresa',
            self::BRANDING => 'Marca e tema',
            self::LOGIN => 'Tela de login',
            self::EMAIL => 'E-mail',
            self::LOCALIZACAO => 'Localização',
            self::SEGURANCA => 'Segurança',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GERAL => 'tabler--building',
            self::BRANDING => 'tabler--palette',
            self::LOGIN => 'tabler--login-2',
            self::EMAIL => 'tabler--mail',
            self::LOCALIZACAO => 'tabler--world',
            self::SEGURANCA => 'tabler--shield-lock',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::GERAL => 'Dados do cliente dono desta instalação.',
            self::BRANDING => 'Logotipos, favicon, nome do sistema e cores do tema.',
            self::LOGIN => 'Aparência e textos da página de login.',
            self::EMAIL => 'Servidor SMTP usado para enviar e-mails.',
            self::LOCALIZACAO => 'Idioma, fuso horário, moeda e formatos.',
            self::SEGURANCA => 'Política de senha, sessão e retenção de logs.',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function abas(): array
    {
        return self::cases();
    }
    case GERAL = 'general';
    case BRANDING = 'branding';
    case LOGIN = 'login';
    case EMAIL = 'email';
    case LOCALIZACAO = 'localizacao';
    case SEGURANCA = 'seguranca';
}
