<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\PerfilConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('atualiza o nome do próprio usuário', function (): void {
    $user = criarAdminUser('perfil@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)
        ->set('nome', 'Nome Novo')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($user->fresh()->nome)->toBe('Nome Novo');
});

it('faz upload do avatar e grava o caminho', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('perfil@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)
        ->set('nome', 'Usuário Teste')
        ->set('avatar', UploadedFile::fake()->image('foto.jpg', 256, 256))
        ->call('salvar')
        ->assertHasNoErrors();

    $caminho = $user->fresh()->avatar_url;

    expect($caminho)->not->toBeNull();
    Storage::disk('public')->assertExists($caminho);
});

it('remove o avatar atual', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('perfil@teste.com');
    $user->forceFill(['avatar_url' => 'avatars/antiga.jpg'])->save();
    Storage::disk('public')->put('avatars/antiga.jpg', 'x');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)->call('removerAvatar');

    expect($user->fresh()->avatar_url)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/antiga.jpg');
});
