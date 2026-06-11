<?php

declare(strict_types=1);

namespace App\Actions\Admin\Convites;

use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Models\AdminUserConvite;
use App\Services\Admin\AuditoriaSeguranca;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Aceita um convite: valida o token, define a senha escolhida pelo convidado e
 * marca o e-mail como verificado. Token consumido é single-use.
 */
class AceitarConviteAction
{
    public function __construct(private readonly AuditoriaSeguranca $auditoria) {}

    public function execute(string $token, string $password): AdminUser
    {
        $convite = AdminUserConvite::localizarPorToken($token);

        if ($convite === null || ! $convite->estaUtilizavel()) {
            throw AccessException::conviteInvalido();
        }

        $usuario = $convite->adminUser;

        if ($usuario === null || ! $usuario->ativo || $usuario->estaAnonimizado()) {
            throw AccessException::conviteInvalido();
        }

        DB::transaction(function () use ($convite, $usuario, $password): void {
            $usuario->forceFill([
                'password' => Hash::make($password),
                'email_verified_at' => $usuario->email_verified_at ?? now(),
            ])->setRememberToken(Str::random(60));
            $usuario->save();

            $convite->forceFill(['aceito_em' => now()])->save();
        });

        $this->auditoria->conviteAceito($usuario);

        return $usuario;
    }
}
