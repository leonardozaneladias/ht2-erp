<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuracao\AbaDangerZone;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Settings\AppearanceSettings;
use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('exige a confirmação digitada para resetar a aparência', function () {
    $admin = criarAdminUser('danger@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaDangerZone::class)
        ->set('confirmacaoReset', 'qualquer-coisa')
        ->call('resetarAparencia')
        ->assertHasErrors('confirmacaoReset');
});

it('restaura identidade e aparência ao padrão HT2', function () {
    $admin = criarAdminUser('danger@config.test');
    $admin->assignRole('super-admin');

    $branding = app(BrandingSettings::class);
    $branding->cor_primaria = '#000000';
    $branding->logo_arquivo = 'branding/custom.png';
    $branding->save();

    $appearance = app(AppearanceSettings::class);
    $appearance->tema_padrao = 'dark';
    $appearance->skin = 'minimal';
    $appearance->save();

    Livewire::actingAs($admin, 'admin')
        ->test(AbaDangerZone::class)
        ->set('confirmacaoReset', 'RESETAR')
        ->call('resetarAparencia')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(app(BrandingSettings::class)->cor_primaria)->toBe('#1577ce')
        ->and(app(BrandingSettings::class)->logo_arquivo)->toBe('')
        ->and(app(AppearanceSettings::class)->tema_padrao)->toBe('light')
        ->and(app(AppearanceSettings::class)->skin)->toBe('default');
});

it('expurga logs após confirmação digitada', function () {
    $admin = criarAdminUser('danger@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaDangerZone::class)
        ->set('confirmacaoExpurgo', 'EXPURGAR')
        ->call('expurgarLogs')
        ->assertHasNoErrors();
});
