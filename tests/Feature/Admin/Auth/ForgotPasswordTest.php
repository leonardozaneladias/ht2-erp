<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\ForgotPassword;
use App\Models\AdminUser;
use App\Notifications\ResetSenhaNotification as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de recuperação de senha', function () {
    Livewire::test(ForgotPassword::class)
        ->assertSee('Esqueceu sua senha?')
        ->assertSee('Enviar link de redefinição');
});

it('exibe erro de validação para e-mail inválido', function () {
    Livewire::test(ForgotPassword::class)
        ->set('email', 'nao-e-um-email')
        ->call('sendLink')
        ->assertHasErrors(['email' => 'email']);
});

it('envia notificação e limpa o campo ao enviar link para e-mail existente', function () {
    Notification::fake();

    $admin = AdminUser::create([
        'nome' => 'Admin',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'admin@teste.com')
        ->call('sendLink')
        ->assertHasNoErrors()
        ->assertSet('email', '');

    Notification::assertSentTo($admin, ResetPasswordNotification::class);
});

it('exibe erro para e-mail inexistente', function () {
    Livewire::test(ForgotPassword::class)
        ->set('email', 'nao-existe@teste.com')
        ->call('sendLink')
        ->assertHasErrors(['email']);
});
