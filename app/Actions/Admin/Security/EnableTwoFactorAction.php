<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Models\AdminUser;
use App\Services\Admin\Security\TwoFactorService;

/**
 * Inicia a ativação do 2FA: gera um segredo pendente (ainda não confirmado).
 * Retorna o segredo para exibição do QR Code.
 */
final class EnableTwoFactorAction
{
    public function __construct(private readonly TwoFactorService $service) {}

    public function execute(AdminUser $usuario): string
    {
        $secret = $this->service->gerarSecret();

        $usuario->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }
}
