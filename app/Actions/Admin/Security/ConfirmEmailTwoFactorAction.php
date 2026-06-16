<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Models\AdminUser;
use App\Services\Admin\Security\AlertaSeguranca;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;

/**
 * Confirma a ativação do 2FA por e-mail validando o código enviado. Em caso de
 * sucesso, liga a preferência do usuário, registra a auditoria e alerta o titular.
 */
final class ConfirmEmailTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $service,
        private readonly AlertaSeguranca $alerta,
    ) {}

    public function execute(AdminUser $usuario, string $codigo): bool
    {
        if (! $this->service->verificarCodigoEmail($usuario, $codigo)) {
            return false;
        }

        $usuario->forceFill([
            'two_factor_email_enabled' => true,
            'two_factor_email_enabled_at' => now(),
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-email-enabled')
            ->log('Verificação em duas etapas por e-mail ativada');

        $this->alerta->doisFatoresEmailAtivado($usuario);

        return true;
    }
}
