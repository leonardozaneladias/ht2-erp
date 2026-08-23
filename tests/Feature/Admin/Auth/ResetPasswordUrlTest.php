<?php

declare(strict_types=1);

use HT2ML\Core\Notifications\ResetSenhaNotification as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('o e-mail de reset de senha aponta para a rota admin (não a rota padrão inexistente)', function (): void {
    $user = criarAdminUser('u@teste.com');
    $token = Password::broker('admins')->createToken($user);

    // Sem o createUrlUsing, toMail() resolve route('password.reset') — inexistente
    // neste projeto (a rota é admin.password.reset) — e lança RouteNotFoundException.
    $mail = (new ResetPasswordNotification($token))->toMail($user);

    expect($mail->actionUrl)
        ->toContain('/admin/resetar-senha/')
        ->toContain('email=u%40teste.com');
});
