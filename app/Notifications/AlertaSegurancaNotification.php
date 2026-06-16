<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TipoAlertaSeguranca;
use App\Enums\TipoNotificacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta de atividade suspeita enviado por e-mail aos super-admins. Enfileirado
 * na fila "emails" para não bloquear o fluxo que o disparou.
 */
final class AlertaSegurancaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $contexto
     */
    public function __construct(
        public readonly TipoAlertaSeguranca $tipo,
        public readonly array $contexto,
    ) {
        $this->onQueue('emails');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Segurança] ' . $this->tipo->label())
            ->line($this->tipo->descricao());

        foreach ($this->contexto as $rotulo => $valor) {
            $mail->line(ucfirst($rotulo) . ': ' . $valor);
        }

        return $this->tipo->paraUsuario()
            ? $mail->action('Revisar segurança da conta', $this->url())
            : $mail->line('Verifique a trilha de auditoria em /admin/auditoria.');
    }

    /**
     * Payload persistido pelo canal database e consumido pelo sino/central in-app.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->tipoNotificacao()->value,
            'titulo' => $this->tipo->label(),
            'mensagem' => $this->tipo->descricao(),
            'icon' => $this->tipoNotificacao()->icon(),
            'url' => $this->url(),
            'contexto' => $this->contexto,
        ];
    }

    /**
     * Destino in-app do alerta: a própria segurança da conta (alertas do dono)
     * ou a trilha de auditoria (alertas operacionais aos super-admins).
     */
    private function url(): string
    {
        return $this->tipo->paraUsuario()
            ? route('admin.conta', ['aba' => 'seguranca'])
            : route('admin.auditoria.index');
    }

    /**
     * Mapeia o alerta de segurança para a categoria visual da notificação in-app.
     */
    private function tipoNotificacao(): TipoNotificacao
    {
        return match ($this->tipo) {
            TipoAlertaSeguranca::LoginSuperAdmin,
            TipoAlertaSeguranca::DoisFatoresAtivado,
            TipoAlertaSeguranca::DoisFatoresEmailAtivado,
            TipoAlertaSeguranca::CodigosRecuperacaoRegenerados => TipoNotificacao::Info,
            default => TipoNotificacao::Aviso,
        };
    }
}
