<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Services\Admin\Security\AlertaSeguranca;
use App\Services\Admin\Security\TwoFactorService;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;

/**
 * Confirma a ativação do 2FA validando o primeiro código TOTP. Em caso de
 * sucesso, gera os códigos de recuperação (retornados em texto puro uma vez)
 * e registra o timestamp do código para impedir replay no primeiro desafio.
 */
final class ConfirmTwoFactorAction
{
    public function __construct(
        private readonly TwoFactorService $service,
        private readonly AlertaSeguranca $alerta,
    ) {}

    /**
     * @return list<string>|null Códigos de recuperação, ou null se o código for inválido.
     */
    public function execute(AdminUser $usuario, string $codigo): ?array
    {
        if ($usuario->two_factor_secret === null) {
            return null;
        }

        $timestamp = $this->service->verificarCodigo(
            $usuario->two_factor_secret,
            $codigo,
            $usuario->two_factor_last_timestamp,
        );

        if ($timestamp === false) {
            return null;
        }

        $codigos = $this->service->gerarRecoveryCodes();

        $usuario->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->service->hashearRecoveryCodes($codigos),
            'two_factor_last_timestamp' => $timestamp,
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-enabled')
            ->log('Autenticação em dois fatores ativada');

        $this->alerta->doisFatoresAtivado($usuario);

        return $codigos;
    }
}
