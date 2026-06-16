<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Models\AdminUser;
use App\Services\Admin\Security\AlertaSeguranca;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;

final class RegenerateRecoveryCodesAction
{
    public function __construct(
        private readonly TwoFactorService $service,
        private readonly AlertaSeguranca $alerta,
    ) {}

    /**
     * @return list<string> Novos códigos de recuperação (texto puro, exibidos uma vez).
     */
    public function execute(AdminUser $usuario): array
    {
        $codigos = $this->service->gerarRecoveryCodes();

        $usuario->forceFill([
            'two_factor_recovery_codes' => $this->service->hashearRecoveryCodes($codigos),
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-recovery-regenerated')
            ->log('Códigos de recuperação 2FA regenerados');

        $this->alerta->codigosRecuperacaoRegenerados($usuario);

        return $codigos;
    }
}
