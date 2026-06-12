<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\FormUsuario;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');
});

it('cria usuário com avatar e grava o arquivo', function () {
    Notification::fake();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Com Foto')
        ->set('email', 'foto@teste.com')
        ->set('avatar', UploadedFile::fake()->image('a.jpg', 512, 512))
        ->call('salvar')
        ->assertHasNoErrors();

    $novo = AdminUser::where('email', 'foto@teste.com')->firstOrFail();

    expect($novo->avatar_url)->not->toBeNull();
    Storage::disk('public')->assertExists($novo->avatar_url);
});

it('substituir avatar na edição apaga o arquivo anterior', function () {
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->forceFill(['avatar_url' => 'avatars/antiga.jpg'])->save();
    Storage::disk('public')->put('avatars/antiga.jpg', 'x');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('avatar', UploadedFile::fake()->image('nova.jpg', 512, 512))
        ->call('salvar')
        ->assertHasNoErrors();

    $alvo->refresh();

    expect($alvo->avatar_url)->not->toBe('avatars/antiga.jpg');
    Storage::disk('public')->assertMissing('avatars/antiga.jpg');
    Storage::disk('public')->assertExists($alvo->avatar_url);
});

it('remove a foto de um usuário pela edição', function () {
    $alvo = criarAdminUser('alvo2@teste.com');
    $alvo->forceFill(['avatar_url' => 'avatars/remover.jpg'])->save();
    Storage::disk('public')->put('avatars/remover.jpg', 'x');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->call('removerFoto')
        ->assertHasNoErrors();

    expect($alvo->fresh()->avatar_url)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/remover.jpg');
});

it('rejeita arquivo que não é imagem e imagem pequena demais', function () {
    $alvo = criarAdminUser('alvo3@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('avatar', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->call('salvar')
        ->assertHasErrors(['avatar']);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('avatar', UploadedFile::fake()->image('mini.jpg', 50, 50))
        ->call('salvar')
        ->assertHasErrors(['avatar']);
});

it('audita quando um admin altera a foto de OUTRO usuário', function () {
    $alvo = criarAdminUser('auditado@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('avatar', UploadedFile::fake()->image('a.jpg', 512, 512))
        ->call('salvar')
        ->assertHasNoErrors();

    expect(Activity::where('log_name', 'admin_users')
        ->where('event', 'avatar_updated')
        ->where('subject_id', $alvo->id)
        ->where('causer_id', $this->admin->id)
        ->exists())->toBeTrue();
});

it('não audita quando o usuário troca a própria foto no Minha Conta', function () {
    $user = criarAdminUser('proprio@teste.com');

    Livewire::actingAs($user, 'admin')
        ->test(App\Livewire\Admin\Conta\PerfilConta::class)
        ->set('avatar', UploadedFile::fake()->image('p.jpg', 256, 256))
        ->call('salvar')
        ->assertHasNoErrors();

    expect(Activity::where('event', 'avatar_updated')->exists())->toBeFalse();
});
