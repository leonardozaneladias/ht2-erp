<?php

declare(strict_types=1);

namespace HT2ML\Core\Notifications;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Convite de acesso ao painel: link para o convidado definir a própria senha.
 * Enfileirado na fila "emails". Recebe o token em claro (nunca persistido).
 */
final class ConviteUsuarioNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly DateTimeInterface $expiraEm,
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
        $url = route('admin.convite.aceitar', ['token' => $this->token]);
        $validade = $this->expiraEm->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject('Você foi convidado para acessar o painel')
            ->greeting('Olá!')
            ->line('Uma conta de acesso ao painel administrativo foi criada para você.')
            ->line('Clique no botão abaixo para definir sua senha e ativar a conta.')
            ->action('Definir minha senha', $url)
            ->line("O convite é válido até {$validade}.")
            ->line('Se você não esperava este convite, ignore este e-mail.');
    }
}
