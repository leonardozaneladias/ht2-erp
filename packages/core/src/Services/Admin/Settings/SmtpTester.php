<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\EmailSettingsDTO;
use Illuminate\Support\Facades\Mail;

/**
 * Envia um e-mail de teste aplicando a configuração informada ao mailer SMTP
 * em runtime — permite validar as credenciais antes de salvá-las.
 *
 * O envio é síncrono de propósito: a tela precisa do resultado imediato da
 * conexão (sucesso ou exceção), o que não seria possível enfileirando.
 */
final class SmtpTester
{
    public function enviar(EmailSettingsDTO $dto, string $destinatario): void
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        config([
            'mail.mailers.smtp.host' => $dto->smtp_host,
            'mail.mailers.smtp.port' => $dto->smtp_port,
            'mail.mailers.smtp.username' => $dto->smtp_username !== '' ? $dto->smtp_username : null,
            'mail.mailers.smtp.password' => $dto->smtp_password !== '' ? $dto->smtp_password : null,
            'mail.mailers.smtp.scheme' => $dto->criptografia === 'ssl' ? 'smtps' : null,
            'mail.from.address' => $dto->from_email !== '' ? $dto->from_email : "nao-responda@{$host}",
            'mail.from.name' => $dto->from_name !== '' ? $dto->from_name : (string) config('app.name'),
        ]);

        Mail::mailer('smtp')->raw(
            'Este é um e-mail de teste enviado pela tela de Configurações do sistema. '
            . 'Se você o recebeu, o servidor SMTP está configurado corretamente.',
            function ($message) use ($destinatario): void {
                $message->to($destinatario)->subject('Teste de configuração de e-mail');
            },
        );
    }
}
