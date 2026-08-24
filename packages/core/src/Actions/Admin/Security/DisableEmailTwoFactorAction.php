<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Security;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use Illuminate\Support\Facades\Auth;

/**
 * Desativa o 2FA por e-mail do usuário, registra a auditoria e alerta o titular.
 */
final class DisableEmailTwoFactorAction
{
    public function __construct(private readonly AlertaSeguranca $alerta) {}

    public function execute(AdminUser $usuario): void
    {
        $usuario->forceFill([
            'two_factor_email_enabled' => false,
            'two_factor_email_enabled_at' => null,
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-email-disabled')
            ->log('Verificação em duas etapas por e-mail desativada');

        $this->alerta->doisFatoresEmailDesativado($usuario);
    }
}
