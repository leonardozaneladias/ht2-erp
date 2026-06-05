<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use App\Enums\TipoAlertaSeguranca;
use App\Models\AdminUser;
use App\Notifications\AlertaSegurancaNotification;
use App\Settings\SegurancaSettings;
use Illuminate\Support\Facades\Notification;

/**
 * Dispara alertas de segurança por e-mail aos super-admins ativos. Respeita os
 * toggles de SegurancaSettings; é no-op quando desabilitado ou sem destinatários.
 */
final class AlertaSeguranca
{
    public function contaBloqueada(AdminUser $usuario): void
    {
        $this->enviar(TipoAlertaSeguranca::ContaBloqueada, [
            'usuario' => (string) $usuario->getAttribute('nome'),
            'email' => (string) $usuario->getAttribute('email'),
        ]);
    }

    public function personificacaoIniciada(AdminUser $ator, AdminUser $alvo): void
    {
        $this->enviar(TipoAlertaSeguranca::PersonificacaoIniciada, [
            'ator' => (string) $ator->getAttribute('nome'),
            'alvo' => (string) $alvo->getAttribute('nome'),
        ]);
    }

    public function superAdminLogou(AdminUser $usuario): void
    {
        if (! app(SegurancaSettings::class)->alerta_login_super_admin) {
            return;
        }

        $this->enviar(TipoAlertaSeguranca::LoginSuperAdmin, [
            'usuario' => (string) $usuario->getAttribute('nome'),
            'email' => (string) $usuario->getAttribute('email'),
        ]);
    }

    /**
     * @param  array<string, string>  $contexto
     */
    private function enviar(TipoAlertaSeguranca $tipo, array $contexto): void
    {
        if (! app(SegurancaSettings::class)->alertas_seguranca_habilitados) {
            return;
        }

        $destinatarios = AdminUser::query()->role('super-admin')->where('ativo', true)->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        Notification::send($destinatarios, new AlertaSegurancaNotification($tipo, $contexto));
    }
}
