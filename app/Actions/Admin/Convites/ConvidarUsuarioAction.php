<?php

declare(strict_types=1);

namespace App\Actions\Admin\Convites;

use App\Notifications\ConviteUsuarioNotification;
use App\Services\Admin\AuditoriaSeguranca;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\AdminUserConvite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emite (ou reemite) o convite de acesso de um usuário: invalida convites
 * pendentes anteriores, gera token novo e envia o e-mail enfileirado.
 */
class ConvidarUsuarioAction
{
    public const VALIDADE_DIAS = 7;

    public function __construct(private readonly AuditoriaSeguranca $auditoria) {}

    public function execute(AdminUser $usuario, ?AdminUser $enviadoPor = null): AdminUserConvite
    {
        $token = Str::random(64);

        $convite = DB::transaction(function () use ($usuario, $enviadoPor, $token): AdminUserConvite {
            // Reenvio invalida o link anterior — só o convite mais recente vale.
            $usuario->convites()->pendentes()->update(['expira_em' => now()]);

            return AdminUserConvite::create([
                'admin_user_id' => $usuario->id,
                'token_hash' => hash('sha256', $token),
                'expira_em' => now()->addDays(self::VALIDADE_DIAS),
                'enviado_por' => $enviadoPor?->id,
            ]);
        });

        $usuario->notify(new ConviteUsuarioNotification($token, $convite->expira_em));

        $this->auditoria->conviteEnviado($usuario, $enviadoPor);

        return $convite;
    }
}
