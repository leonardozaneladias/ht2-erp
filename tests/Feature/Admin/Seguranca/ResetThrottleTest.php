<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Auth\ForgotPassword;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('throttla solicitações de reset após o limite', function (): void {
    Notification::fake();
    $s = app(SegurancaSettings::class);
    $s->login_max_tentativas = 2;
    $s->save();

    criarAdminUser('u@teste.com');

    // Faz o broker sempre retornar RESET_LINK_SENT para isolar o throttle da nossa camada
    Password::shouldReceive('broker')
        ->with('admins')
        ->andReturn(new class
        {
            public function sendResetLink(array $credentials): string
            {
                return Password::RESET_LINK_SENT;
            }
        });

    foreach (range(1, 2) as $_) {
        Livewire::test(ForgotPassword::class)->set('email', 'u@teste.com')->call('sendLink')->assertHasNoErrors();
    }

    Livewire::test(ForgotPassword::class)
        ->set('email', 'u@teste.com')
        ->call('sendLink')
        ->assertHasErrors('email');
});
