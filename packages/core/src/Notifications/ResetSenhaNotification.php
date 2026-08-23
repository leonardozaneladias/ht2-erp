<?php

declare(strict_types=1);

namespace HT2ML\Core\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Reset de senha do guard admin, enfileirado na fila "emails" para não bloquear
 * a request. A URL continua vinda do ResetPassword::createUrlUsing() registrado
 * no AppServiceProvider (rota admin.password.reset).
 */
final class ResetSenhaNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);

        $this->onQueue('emails');
    }
}
