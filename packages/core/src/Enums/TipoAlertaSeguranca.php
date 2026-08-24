<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums;

enum TipoAlertaSeguranca: string
{
    public function label(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Conta bloqueada por falhas de login',
            self::PersonificacaoIniciada => 'Personificação iniciada',
            self::LoginSuperAdmin => 'Login de super-administrador',
            self::DoisFatoresAtivado => 'Verificação em duas etapas ativada',
            self::DoisFatoresDesativado => 'Verificação em duas etapas desativada',
            self::CodigosRecuperacaoRegenerados => 'Códigos de recuperação regenerados',
            self::CodigoRecuperacaoUtilizado => 'Código de recuperação utilizado',
            self::DoisFatoresEmailAtivado => 'Código por e-mail ativado',
            self::DoisFatoresEmailDesativado => 'Código por e-mail desativado',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Uma conta foi bloqueada temporariamente após exceder o limite de tentativas de login.',
            self::PersonificacaoIniciada => 'Um administrador iniciou uma personificação (act-as) de outro usuário.',
            self::LoginSuperAdmin => 'Um super-administrador autenticou no painel.',
            self::DoisFatoresAtivado => 'A verificação em duas etapas foi ativada na sua conta.',
            self::DoisFatoresDesativado => 'A verificação em duas etapas foi desativada na sua conta. Se não foi você, redefina sua senha imediatamente.',
            self::CodigosRecuperacaoRegenerados => 'Novos códigos de recuperação foram gerados na sua conta; os códigos anteriores deixaram de valer.',
            self::CodigoRecuperacaoUtilizado => 'Um código de recuperação foi usado para acessar sua conta. Se não foi você, redefina sua senha imediatamente.',
            self::DoisFatoresEmailAtivado => 'O recebimento de código por e-mail foi ativado como segundo fator na sua conta.',
            self::DoisFatoresEmailDesativado => 'O recebimento de código por e-mail foi desativado na sua conta. Se não foi você, redefina sua senha imediatamente.',
        };
    }

    /**
     * Alertas direcionados ao próprio dono da conta (em vez dos super-admins).
     * Definem o destinatário, a URL in-app e o tom da notificação.
     */
    public function paraUsuario(): bool
    {
        return match ($this) {
            self::DoisFatoresAtivado,
            self::DoisFatoresDesativado,
            self::DoisFatoresEmailAtivado,
            self::DoisFatoresEmailDesativado,
            self::CodigosRecuperacaoRegenerados,
            self::CodigoRecuperacaoUtilizado => true,
            default => false,
        };
    }

    case ContaBloqueada = 'conta-bloqueada';
    case PersonificacaoIniciada = 'personificacao-iniciada';
    case LoginSuperAdmin = 'login-super-admin';
    case DoisFatoresAtivado = 'dois-fatores-ativado';
    case DoisFatoresDesativado = 'dois-fatores-desativado';
    case CodigosRecuperacaoRegenerados = 'codigos-recuperacao-regenerados';
    case CodigoRecuperacaoUtilizado = 'codigo-recuperacao-utilizado';
    case DoisFatoresEmailAtivado = 'dois-fatores-email-ativado';
    case DoisFatoresEmailDesativado = 'dois-fatores-email-desativado';
}
