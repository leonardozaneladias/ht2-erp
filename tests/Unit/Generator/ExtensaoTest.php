<?php

declare(strict_types=1);

use App\Support\Generator\Extensao;

const CONFIG_FAKE = [
    'vendor' => 'ht2ml',
    'namespace' => 'HT2ML',
    'path' => 'packages',
    'prefixo_pacote' => 'extensao-',
];

it('deriva a identidade de uma extensão de nome simples', function () {
    $m = Extensao::criar('Rh', CONFIG_FAKE);

    expect($m->studly)->toBe('Rh')
        ->and($m->slug)->toBe('rh')
        ->and($m->namespaceBase)->toBe('HT2ML\\Rh')
        ->and($m->pacote)->toBe('ht2ml/extensao-rh')
        ->and($m->dir)->toBe('packages/extensao-rh')
        ->and($m->viewNamespace)->toBe('rh')
        ->and($m->providerClass)->toBe('RhServiceProvider')
        ->and($m->providerFqn())->toBe('HT2ML\\Rh\\RhServiceProvider');
});

it('deriva a identidade de uma extensão de nome composto', function () {
    $m = Extensao::criar('RecursosHumanos', CONFIG_FAKE);

    expect($m->slug)->toBe('recursos-humanos')
        ->and($m->pacote)->toBe('ht2ml/extensao-recursos-humanos')
        ->and($m->dir)->toBe('packages/extensao-recursos-humanos')
        ->and($m->namespaceBase)->toBe('HT2ML\\RecursosHumanos')
        ->and($m->providerFqn())->toBe('HT2ML\\RecursosHumanos\\RecursosHumanosServiceProvider');
});

it('normaliza nome em minúsculas ou plural para StudlyCase singular-conservado', function () {
    expect(Extensao::criar('financeiro', CONFIG_FAKE)->studly)->toBe('Financeiro')
        ->and(Extensao::criar('financeiro', CONFIG_FAKE)->slug)->toBe('financeiro');
});
