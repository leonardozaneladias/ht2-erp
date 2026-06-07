<?php

declare(strict_types=1);

use App\Enums\TipoAlertaSeguranca;
use App\Enums\TipoNotificacao;
use App\Notifications\AlertaSegurancaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('o alerta de segurança usa os canais mail e database', function (): void {
    $notificacao = new AlertaSegurancaNotification(TipoAlertaSeguranca::ContaBloqueada, []);

    expect($notificacao->via(new stdClass))->toContain('database')->toContain('mail');
});

it('o payload database do alerta tem a estrutura consumida pelo sino', function (): void {
    $notificacao = new AlertaSegurancaNotification(
        TipoAlertaSeguranca::LoginSuperAdmin,
        ['IP' => '10.0.0.1'],
    );

    $dados = $notificacao->toArray(new stdClass);

    expect($dados)
        ->toHaveKeys(['tipo', 'titulo', 'mensagem', 'icon', 'url', 'contexto'])
        ->and($dados['tipo'])->toBe(TipoNotificacao::Info->value)
        ->and($dados['contexto'])->toBe(['IP' => '10.0.0.1']);
});

it('grava a notificação na tabela ao disparar o alerta', function (): void {
    $user = criarAdminUser('alerta@teste.com');

    $user->notifyNow(new AlertaSegurancaNotification(TipoAlertaSeguranca::ContaBloqueada, []));

    expect($user->notifications()->count())->toBe(1)
        ->and($user->unreadNotifications()->count())->toBe(1);
});
