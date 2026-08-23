<?php

declare(strict_types=1);

namespace App\Actions\Admin\Security;

use App\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;

final class DisableTwoFactorAction
{
    public function __construct(private readonly AlertaSeguranca $alerta) {}

    public function execute(AdminUser $usuario): void
    {
        $usuario->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestamp' => null,
        ])->save();

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy(Auth::guard('admin')->user())
            ->event('2fa-disabled')
            ->log('Autenticação em dois fatores desativada');

        $this->alerta->doisFatoresDesativado($usuario);
    }
}
