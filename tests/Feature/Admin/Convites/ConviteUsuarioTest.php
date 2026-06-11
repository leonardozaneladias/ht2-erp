<?php

declare(strict_types=1);

use App\Actions\Admin\Convites\AceitarConviteAction;
use App\Actions\Admin\Convites\ConvidarUsuarioAction;
use App\Exceptions\AccessException;
use App\Livewire\Admin\Auth\AceitarConvite;
use App\Livewire\Admin\Usuarios\FormUsuario;
use App\Models\AdminUser;
use App\Notifications\ConviteUsuarioNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function convidar(AdminUser $usuario): array
{
    // Reproduz a action capturando o token em claro via notification fake.
    Notification::fake();
    $convite = app(ConvidarUsuarioAction::class)->execute($usuario);

    $token = null;
    Notification::assertSentTo($usuario, ConviteUsuarioNotification::class, function (ConviteUsuarioNotification $n) use (&$token): bool {
        $token = $n->token;

        return $n->queue === 'emails';
    });

    return [$convite, $token];
}

it('cria usuário por convite a partir do formulário, sem senha manual', function () {
    Notification::fake();

    $admin = criarAdminUser('super@teste.com');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Convidada')
        ->set('email', 'convidada@teste.com')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.usuarios.index'));

    $novo = AdminUser::where('email', 'convidada@teste.com')->firstOrFail();

    expect($novo->convites()->pendentes()->count())->toBe(1)
        ->and(Hash::check('', $novo->password))->toBeFalse()
        ->and($novo->email_verified_at)->toBeNull();

    Notification::assertSentTo($novo, ConviteUsuarioNotification::class);
});

it('aceitar convite define senha, verifica e-mail e consome o token', function () {
    $usuario = criarAdminUser('convite@teste.com');
    [$convite, $token] = convidar($usuario);

    Livewire::test(AceitarConvite::class, ['token' => $token])
        ->assertSet('conviteValido', true)
        ->set('password', 'NovaSenhaForte1!')
        ->set('password_confirmation', 'NovaSenhaForte1!')
        ->call('aceitar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.login'));

    $usuario->refresh();

    expect(Hash::check('NovaSenhaForte1!', $usuario->password))->toBeTrue()
        ->and($usuario->email_verified_at)->not->toBeNull()
        ->and($convite->fresh()->foiAceito())->toBeTrue();

    expect(Activity::where('log_name', 'auth')->where('event', 'convite-aceito')->exists())->toBeTrue();
});

it('registra auditoria ao enviar convite', function () {
    $usuario = criarAdminUser('audit@teste.com');
    convidar($usuario);

    expect(Activity::where('log_name', 'auth')
        ->where('event', 'convite-enviado')
        ->where('subject_id', $usuario->id)
        ->exists())->toBeTrue();
});

it('rejeita token inexistente, expirado e já utilizado', function () {
    $usuario = criarAdminUser('rejeita@teste.com');
    [$convite, $token] = convidar($usuario);

    // Inexistente.
    expect(fn () => app(AceitarConviteAction::class)->execute('token-falso', 'SenhaForte1!'))
        ->toThrow(AccessException::class);

    // Expirado.
    $convite->forceFill(['expira_em' => now()->subMinute()])->save();
    expect(fn () => app(AceitarConviteAction::class)->execute($token, 'SenhaForte1!'))
        ->toThrow(AccessException::class);

    // Utilizado: emite novo, aceita e tenta de novo.
    [, $token2] = convidar($usuario);
    app(AceitarConviteAction::class)->execute($token2, 'SenhaForte1!');
    expect(fn () => app(AceitarConviteAction::class)->execute($token2, 'OutraSenha1!'))
        ->toThrow(AccessException::class);
});

it('rejeita convite de conta desativada', function () {
    $usuario = criarAdminUser('inativa@teste.com');
    [, $token] = convidar($usuario);
    $usuario->forceFill(['ativo' => false])->save();

    expect(fn () => app(AceitarConviteAction::class)->execute($token, 'SenhaForte1!'))
        ->toThrow(AccessException::class);

    Livewire::test(AceitarConvite::class, ['token' => $token])
        ->assertSet('conviteValido', false);
});

it('reenvio invalida o convite pendente anterior', function () {
    $usuario = criarAdminUser('reenvio@teste.com');
    [, $tokenAntigo] = convidar($usuario);
    [, $tokenNovo] = convidar($usuario);

    expect(fn () => app(AceitarConviteAction::class)->execute($tokenAntigo, 'SenhaForte1!'))
        ->toThrow(AccessException::class);

    $aceito = app(AceitarConviteAction::class)->execute($tokenNovo, 'SenhaForte1!');
    expect($aceito->email_verified_at)->not->toBeNull();
});

it('reenviar convite pela tela de edição exige autorização e envia e-mail', function () {
    Notification::fake();

    $admin = criarAdminUser('super2@teste.com');
    $admin->assignRole('super-admin');
    $alvo = criarAdminUser('pendente@teste.com');

    Livewire::actingAs($admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->call('reenviarConvite')
        ->assertHasNoErrors();

    Notification::assertSentTo($alvo, ConviteUsuarioNotification::class);
});

it('valida a política de senha no aceite do convite', function () {
    $usuario = criarAdminUser('fraca@teste.com');
    [, $token] = convidar($usuario);

    Livewire::test(AceitarConvite::class, ['token' => $token])
        ->set('password', '123')
        ->set('password_confirmation', '123')
        ->call('aceitar')
        ->assertHasErrors(['password']);
});
