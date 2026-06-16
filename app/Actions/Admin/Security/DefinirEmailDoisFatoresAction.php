<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Models\AdminUser;
use App\Services\Admin\Security\AlertaSeguranca;

/**
 * Um administrador habilita ou desabilita o 2FA por e-mail de OUTRO usuário.
 * Direto, sem confirmação por código (decisão administrativa): registra a
 * auditoria (ator + alvo) e alerta o titular da conta.
 */
final class DefinirEmailDoisFatoresAction
{
    public function __construct(private readonly AlertaSeguranca $alerta) {}

    public function execute(AdminUser $alvo, bool $habilitar, ?AdminUser $ator = null): void
    {
        if ($alvo->two_factor_email_enabled === $habilitar) {
            return;
        }

        $alvo->forceFill([
            'two_factor_email_enabled' => $habilitar,
            'two_factor_email_enabled_at' => $habilitar ? now() : null,
        ])->save();

        activity('admin_users')
            ->performedOn($alvo)
            ->causedBy($ator)
            ->event($habilitar ? '2fa-email-enabled' : '2fa-email-disabled')
            ->log($habilitar
                ? 'Verificação em duas etapas por e-mail ativada por um administrador'
                : 'Verificação em duas etapas por e-mail desativada por um administrador');

        if ($habilitar) {
            $this->alerta->doisFatoresEmailAtivado($alvo);
        } else {
            $this->alerta->doisFatoresEmailDesativado($alvo);
        }
    }
}
