<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;

/**
 * Centraliza o registro de eventos de segurança/autenticação na trilha de
 * auditoria (log_name "auth"), com nomes de evento e descrições PT-BR padronizados.
 */
final class AuditoriaSeguranca
{
    public function loginBemSucedido(AdminUser $usuario, bool $via2fa): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('login')
            ->withProperties(['2fa' => $via2fa])
            ->log('Login realizado');
    }

    public function loginFalhou(string $email): void
    {
        activity('auth')
            ->event('login-falhou')
            ->withProperties(['email' => $email])
            ->log('Falha de login');
    }

    public function loginBloqueado(string $email): void
    {
        activity('auth')
            ->event('login-bloqueado')
            ->withProperties(['email' => $email])
            ->log('Login bloqueado por excesso de tentativas');
    }

    public function logout(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('logout')
            ->log('Logout realizado');
    }

    public function desafio2faFalhou(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('2fa-desafio-falhou')
            ->log('Falha no desafio de verificação em duas etapas');
    }

    public function senhaResetSolicitada(string $email): void
    {
        activity('auth')
            ->event('senha-reset-solicitado')
            ->withProperties(['email' => $email])
            ->log('Redefinição de senha solicitada');
    }

    public function senhaResetAplicada(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('senha-reset-aplicado')
            ->log('Senha redefinida');
    }

    public function conviteEnviado(AdminUser $usuario, ?AdminUser $enviadoPor = null): void
    {
        activity('auth')
            ->performedOn($usuario)
            ->causedBy($enviadoPor)
            ->event('convite-enviado')
            ->withProperties(['email' => $usuario->email])
            ->log('Convite de acesso enviado');
    }

    public function outrosDispositivosDesconectados(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('sessoes-encerradas')
            ->log('Sessões em outros dispositivos encerradas pelo usuário');
    }

    public function conviteAceito(AdminUser $usuario): void
    {
        activity('auth')
            ->performedOn($usuario)
            ->causedBy($usuario)
            ->event('convite-aceito')
            ->log('Convite de acesso aceito — senha definida pelo usuário');
    }
}
