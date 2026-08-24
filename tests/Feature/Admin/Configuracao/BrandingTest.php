<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Configuracao\AbaAparencia;
use HT2ML\Core\Services\Admin\Settings\BrandingService;
use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('gera CSS variables a partir das cores configuradas', function () {
    $settings = app(BrandingSettings::class);
    $settings->cor_primaria = '#ff0000';
    $settings->save();

    $css = app(BrandingService::class)->cssVariables();

    expect($css)->toContain('--color-primary: #ff0000;')
        ->and($css)->toContain('--color-primary-hover: color-mix')
        ->and($css)->toContain('--color-primary-500: #ff0000;');
});

it('usa o fallback de config quando não há logo enviado', function () {
    $url = app(BrandingService::class)->logoUrl('light');

    expect($url)->toContain((string) config('branding.logo_path'));
});

it('salva branding com upload de logo e recarrega na aba', function () {
    Storage::fake('public');

    $admin = criarAdminUser('brand@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaAparencia::class)
        ->set('nome_sistema', 'ERP do Cliente')
        ->set('cor_primaria', '#123abc')
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 60))
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('appearance-applied');

    $settings = app(BrandingSettings::class);

    expect($settings->nome_sistema)->toBe('ERP do Cliente')
        ->and($settings->cor_primaria)->toBe('#123abc')
        ->and($settings->logo_arquivo)->not->toBe('');

    Storage::disk('public')->assertExists($settings->logo_arquivo);
});

it('rejeita cor em formato inválido', function () {
    $admin = criarAdminUser('brand@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaAparencia::class)
        ->set('cor_primaria', 'vermelho')
        ->call('salvar')
        ->assertHasErrors(['cor_primaria']);
});
