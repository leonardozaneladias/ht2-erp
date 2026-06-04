<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuracao\AbaLogin;
use App\Settings\LoginSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('salva textos e fundo da tela de login', function () {
    Storage::fake('public');

    $admin = criarAdminUser('login@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaLogin::class)
        ->set('titulo', 'Bem-vindo ao ERP')
        ->set('subtitulo', 'Entre com suas credenciais')
        ->set('mostrar_versao', true)
        ->set('fundo', UploadedFile::fake()->image('fundo.jpg', 1600, 1200))
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = app(LoginSettings::class);

    expect($settings->titulo)->toBe('Bem-vindo ao ERP')
        ->and($settings->mostrar_versao)->toBeTrue()
        ->and($settings->fundo_imagem_arquivo)->not->toBe('');

    Storage::disk('public')->assertExists($settings->fundo_imagem_arquivo);
});

it('aplica os textos configurados na tela de login pública', function () {
    $settings = app(LoginSettings::class);
    $settings->titulo = 'Portal do Cliente';
    $settings->subtitulo = 'Acesso restrito';
    $settings->save();

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Portal do Cliente')
        ->assertSee('Acesso restrito');
});

it('exige título na tela de login', function () {
    $admin = criarAdminUser('login@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaLogin::class)
        ->set('titulo', '')
        ->call('salvar')
        ->assertHasErrors(['titulo']);
});
