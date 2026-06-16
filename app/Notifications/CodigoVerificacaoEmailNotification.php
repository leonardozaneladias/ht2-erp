<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Código de verificação em duas etapas enviado por e-mail (método alternativo de
 * 2FA). Enfileirado na fila "emails". Recebe o código em claro (nunca persistido
 * — no servidor fica só o hash, em cache com TTL).
 *
 * Canal exclusivamente "mail": o código jamais vai para o inbox in-app
 * (database), para não vazar o segundo fator a uma sessão já aberta.
 */
final class CodigoVerificacaoEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $codigo,
        public readonly int $validadeMinutos,
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
        return (new MailMessage)
            ->subject('Seu código de verificação')
            ->greeting('Olá!')
            ->line('Use o código abaixo para concluir a verificação em duas etapas:')
            ->line("**{$this->codigo}**")
            ->line("O código é válido por {$this->validadeMinutos} minutos.")
            ->line('Se você não tentou entrar, ignore este e-mail e considere trocar sua senha.');
    }
}
