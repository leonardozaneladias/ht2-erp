<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\ResetPassword;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de redefinição de senha', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->assertSee('Definir nova senha')
        ->assertSee('Nova senha')
        ->assertSee('Confirmar nova senha');
});

it('preenche o token via mount', function () {
    Livewire::test(ResetPassword::class, ['token' => 'meu-token'])
        ->assertSet('token', 'meu-token');
});

it('liga o medidor de força ao campo de senha', function () {
    // Regressão: o meter standalone só ativa via JS quando o Blade emite
    // data-password-meter-standalone="{id-do-input}". O id do campo "password"
    // é "password". Se o call-site usar uma prop inexistente (ex.: target=), o
    // atributo some e o medidor fica inerte (barra nunca atualiza).
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->assertSeeHtml('data-password-meter-standalone="password"');
});

it('exibe erro se as senhas não coincidem', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->set('email', 'admin@teste.com')
        ->set('password', 'nova-senha-123')
        ->set('password_confirmation', 'senha-diferente')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

it('exibe erro se a senha é muito curta', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->set('email', 'admin@teste.com')
        ->set('password', '1234567')
        ->set('password_confirmation', '1234567')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

it('redireciona para login após reset bem-sucedido', function () {
    $admin = AdminUser::create([
        'nome' => 'Admin',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    Event::fake([PasswordReset::class]);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'admin@teste.com')
        ->set('password', 'NovaSenhaSegura1')
        ->set('password_confirmation', 'NovaSenhaSegura1')
        ->call('resetPassword')
        ->assertRedirect(route('admin.login'));

    expect(Hash::check('NovaSenhaSegura1', $admin->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordReset::class);
});
