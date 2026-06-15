<?php

declare(strict_types=1);

use App\Support\Generator\ModuloPacote;

const CONFIG_FAKE = [
    'vendor' => 'ht2erp',
    'namespace' => 'HT2ERP',
    'path' => 'packages',
    'prefixo_pacote' => 'modulo-',
];

it('deriva a identidade de um módulo-pacote de nome simples', function () {
    $m = ModuloPacote::criar('Rh', CONFIG_FAKE);

    expect($m->studly)->toBe('Rh')
        ->and($m->slug)->toBe('rh')
        ->and($m->namespaceBase)->toBe('HT2ERP\\Rh')
        ->and($m->pacote)->toBe('ht2erp/modulo-rh')
        ->and($m->dir)->toBe('packages/modulo-rh')
        ->and($m->viewNamespace)->toBe('rh')
        ->and($m->providerClass)->toBe('RhServiceProvider')
        ->and($m->providerFqn())->toBe('HT2ERP\\Rh\\RhServiceProvider');
});

it('deriva a identidade de um módulo-pacote de nome composto', function () {
    $m = ModuloPacote::criar('RecursosHumanos', CONFIG_FAKE);

    expect($m->slug)->toBe('recursos-humanos')
        ->and($m->pacote)->toBe('ht2erp/modulo-recursos-humanos')
        ->and($m->dir)->toBe('packages/modulo-recursos-humanos')
        ->and($m->namespaceBase)->toBe('HT2ERP\\RecursosHumanos')
        ->and($m->providerFqn())->toBe('HT2ERP\\RecursosHumanos\\RecursosHumanosServiceProvider');
});

it('normaliza nome em minúsculas ou plural para StudlyCase singular-conservado', function () {
    expect(ModuloPacote::criar('financeiro', CONFIG_FAKE)->studly)->toBe('Financeiro')
        ->and(ModuloPacote::criar('financeiro', CONFIG_FAKE)->slug)->toBe('financeiro');
});
