<?php

declare(strict_types=1);

use HT2ML\Core\Enums\TipoAlertaSeguranca;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('monta o e-mail do alerta com assunto e contexto', function (): void {
    $user = criarAdminUser('dest@teste.com');
    $notif = new AlertaSegurancaNotification(TipoAlertaSeguranca::ContaBloqueada, ['usuario' => 'Fulano', 'email' => 'f@x.com']);

    $mail = $notif->toMail($user);

    expect($mail->subject)->toContain('Conta bloqueada')
        ->and(implode(' ', $mail->introLines))->toContain('Fulano');
});
