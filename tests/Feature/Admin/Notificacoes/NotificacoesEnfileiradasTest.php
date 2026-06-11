<?php

declare(strict_types=1);

use App\Notifications\AlertaSegurancaNotification;
use App\Notifications\ComunicadoNotification;
use App\Notifications\ResetSenhaNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('toda notification de e-mail/massa implementa ShouldQueue', function (string $classe) {
    expect(is_subclass_of($classe, ShouldQueue::class))->toBeTrue();
})->with([
    [ComunicadoNotification::class],
    [ResetSenhaNotification::class],
    [AlertaSegurancaNotification::class],
]);

it('o reset de senha do admin usa a notification enfileirada na fila emails', function () {
    Notification::fake();

    $admin = criarAdminUser('reset@teste.com');

    Password::broker('admins')->sendResetLink(['email' => 'reset@teste.com']);

    Notification::assertSentTo(
        $admin,
        ResetSenhaNotification::class,
        fn (ResetSenhaNotification $n): bool => $n->queue === 'emails',
    );
});
