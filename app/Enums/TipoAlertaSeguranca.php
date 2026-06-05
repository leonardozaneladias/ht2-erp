<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAlertaSeguranca: string
{
    public function label(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Conta bloqueada por falhas de login',
            self::PersonificacaoIniciada => 'Personificação iniciada',
            self::LoginSuperAdmin => 'Login de super-administrador',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Uma conta foi bloqueada temporariamente após exceder o limite de tentativas de login.',
            self::PersonificacaoIniciada => 'Um administrador iniciou uma personificação (act-as) de outro usuário.',
            self::LoginSuperAdmin => 'Um super-administrador autenticou no painel.',
        };
    }
    case ContaBloqueada = 'conta-bloqueada';
    case PersonificacaoIniciada = 'personificacao-iniciada';
    case LoginSuperAdmin = 'login-super-admin';
}
