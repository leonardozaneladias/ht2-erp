<?php

declare(strict_types=1);

use App\Services\Admin\Settings\BrandingService;
use App\Settings\BrandingSettings;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prioriza a cor da empresa ativa sobre a da instância', function () {
    $settings = app(BrandingSettings::class);
    $settings->cor_primaria = '#aaaaaa';
    $settings->save();

    $empresa = Empresa::create(['nome' => 'A', 'ativo' => true, 'cor_primaria' => '#123456']);
    app(TenantContext::class)->definirEmpresa($empresa->id);

    expect(app(BrandingService::class)->cssVariables())->toContain('--color-primary: #123456;');
});

it('cai para a cor da instância quando a empresa ativa não define', function () {
    $settings = app(BrandingSettings::class);
    $settings->cor_primaria = '#aaaaaa';
    $settings->save();

    $empresa = Empresa::create(['nome' => 'A', 'ativo' => true]); // sem cor
    app(TenantContext::class)->definirEmpresa($empresa->id);

    expect(app(BrandingService::class)->cssVariables())->toContain('--color-primary: #aaaaaa;');
});

it('usa o logo da empresa ativa quando enviado', function () {
    $empresa = Empresa::create([
        'nome' => 'A',
        'ativo' => true,
        'logo_arquivo' => 'empresas/1/logo.png',
    ]);
    app(TenantContext::class)->definirEmpresa($empresa->id);

    expect(app(BrandingService::class)->logoUrl('light'))->toContain('empresas/1/logo.png');
});

it('sem empresa ativa, o branding permanece o da instância (login/setup)', function () {
    $settings = app(BrandingSettings::class);
    $settings->cor_primaria = '#abcdef';
    $settings->save();

    app(TenantContext::class)->limpar();

    expect(app(BrandingService::class)->cssVariables())->toContain('--color-primary: #abcdef;');
});
