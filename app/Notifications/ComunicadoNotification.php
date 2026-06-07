<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TipoNotificacao;
use Illuminate\Notifications\Notification;

/**
 * Comunicado in-app composto manualmente por um administrador e entregue aos
 * usuários pelo canal database (aparece no sino e na central).
 */
final class ComunicadoNotification extends Notification
{
    public function __construct(
        public readonly TipoNotificacao $tipo,
        public readonly string $titulo,
        public readonly string $mensagem,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->tipo->value,
            'titulo' => $this->titulo,
            'mensagem' => $this->mensagem,
            'icon' => $this->tipo->icon(),
            'url' => null,
        ];
    }
}
