<?php

declare(strict_types=1);

use App\Services\Admin\Settings\AppearanceService;
use App\Settings\AppearanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('retorna os defaults de aparência da instância', function () {
    $service = app(AppearanceService::class);

    expect($service->temaPadrao())->toBe('light')
        ->and($service->skin())->toBe('default')
        ->and($service->topbarColor())->toBe('light')
        ->and($service->menuColor())->toBe('dark')
        ->and($service->layoutWidth())->toBe('fluid')
        ->and($service->sidenavSizePadrao())->toBe('default')
        ->and($service->sidenavUser())->toBeTrue();
});

it('cai para o padrão quando o valor salvo é inválido (defesa contra injeção)', function () {
    $settings = app(AppearanceSettings::class);
    $settings->skin = 'inexistente';
    $settings->tema_padrao = 'roxo';
    $settings->menu_color = '"><script>';
    $settings->save();

    $service = app(AppearanceService::class);

    expect($service->skin())->toBe('default')
        ->and($service->temaPadrao())->toBe('light')
        ->and($service->menuColor())->toBe('dark');
});

it('paraThemeConfig mapeia a cor do menu para a chave sidenav-color', function () {
    $settings = app(AppearanceSettings::class);
    $settings->menu_color = 'gray';
    $settings->topbar_color = 'dark';
    $settings->save();

    $config = app(AppearanceService::class)->paraThemeConfig();

    expect($config)->toHaveKey('sidenav-color', 'gray')
        ->and($config)->toHaveKey('topbar-color', 'dark')
        ->and($config)->toHaveKey('position', 'fixed');
});
