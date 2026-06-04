<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Models\AdminUser;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;

/**
 * Confirma a ativação do 2FA validando o primeiro código TOTP. Em caso de
 * sucesso, gera os códigos de recuperação (retornados em texto puro uma vez).
 */
final class ConfirmTwoFactorAction
{
    public function __construct(private readonly TwoFactorService $service) {}

    /**
     * @return list<string>|null Códigos de recuperação, ou null se o código for inválido.
     */
    public function execute(AdminUser $usuario, string $codigo): ?array
    {
        if ($usuario->two_factor_secret === null
            || ! $this->service->verificarCodigo($usuario->two_factor_secret, $codigo)) {
            return null;
        }

        $codigos = $this->service->gerarRecoveryCodes();

        $usuario->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->service->hashearRecoveryCodes($codigos),
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-enabled')
            ->log('Autenticação em dois fatores ativada');

        return $codigos;
    }
}
