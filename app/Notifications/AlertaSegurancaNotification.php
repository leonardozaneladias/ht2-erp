<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TipoAlertaSeguranca;
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Segurança] ' . $this->tipo->label())
            ->line($this->tipo->descricao());

        foreach ($this->contexto as $rotulo => $valor) {
            $mail->line(ucfirst($rotulo) . ': ' . $valor);
        }

        return $mail->line('Verifique a trilha de auditoria em /admin/auditoria.');
    }
}
