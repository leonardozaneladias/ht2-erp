<?php

declare(strict_types=1);

use Database\Seeders\Referencia\BancoSeeder;
use Database\Seeders\Referencia\CargoSeeder;
use Database\Seeders\Referencia\MoedaSeeder;
use Database\Seeders\Referencia\TipoLogradouroSeeder;
use HT2ML\Core\Models\Referencia\Banco;
use HT2ML\Core\Models\Referencia\Cargo;
use HT2ML\Core\Models\Referencia\Moeda;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use HT2ML\FiscalBr\Database\Seeders\CfopSeeder;
use HT2ML\FiscalBr\Database\Seeders\CnaeSeeder;
use HT2ML\FiscalBr\Database\Seeders\NcmSeeder;
use HT2ML\FiscalBr\Enums\TipoCfop;
use HT2ML\FiscalBr\Models\Cfop;
use HT2ML\FiscalBr\Models\Cnae;
use HT2ML\FiscalBr\Models\Ncm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        MoedaSeeder::class,
        BancoSeeder::class,
        CargoSeeder::class,
        TipoLogradouroSeeder::class,
        CnaeSeeder::class,
        CfopSeeder::class,
        NcmSeeder::class,
    ]);
});

it('semeia financeiro, RH e fiscal com as contagens-âncora', function () {
    expect(Moeda::count())->toBe(35)
        ->and(Banco::count())->toBeGreaterThanOrEqual(400)
        ->and(Cargo::count())->toBeGreaterThanOrEqual(20)
        ->and(TipoLogradouro::count())->toBeGreaterThanOrEqual(25)
        ->and(Cnae::count())->toBeGreaterThanOrEqual(1300)
        ->and(Cfop::count())->toBeGreaterThanOrEqual(25)
        ->and(Ncm::count())->toBeGreaterThanOrEqual(10000);
});

it('deriva o tipo do CFOP pelo 1º dígito', function () {
    expect(Cfop::query()->where('codigo', '5102')->firstOrFail()->tipo)->toBe(TipoCfop::Saida)
        ->and(Cfop::query()->where('codigo', '1102')->firstOrFail()->tipo)->toBe(TipoCfop::Entrada);
});

it('moeda BRL e Banco do Brasil presentes com os campos corretos', function () {
    $brl = Moeda::query()->where('codigo_iso', 'BRL')->firstOrFail();
    expect($brl->casas_decimais)->toBe(2)
        ->and($brl->simbolo)->toBe('R$');

    expect(Banco::query()->where('codigo_compe', '001')->exists())->toBeTrue();
});

it('é idempotente: re-seed não duplica os catálogos', function () {
    $antes = [Moeda::count(), Banco::count(), Cnae::count(), Cfop::count(), Ncm::count()];

    $this->seed([MoedaSeeder::class, BancoSeeder::class, CnaeSeeder::class, CfopSeeder::class, NcmSeeder::class]);

    expect([Moeda::count(), Banco::count(), Cnae::count(), Cfop::count(), Ncm::count()])->toBe($antes);
});

it('preserva a descrição completa do NCM (não trunca em 500 chars)', function () {
    expect(Ncm::query()->whereRaw('length(descricao) > 500')->exists())->toBeTrue();
});

it('spot-check NCM/CNAE: código casa com a descrição (sem coluna deslocada)', function () {
    // 84713012 tem vírgulas no meio (campo entre aspas): se as aspas não fossem
    // respeitadas, a descrição quebraria na vírgula do decimal "3,5". Conter "tela"
    // (depois dessa vírgula) prova que o campo inteiro caiu na coluna certa.
    $ncmLongo = (string) Ncm::query()->where('codigo', '84713012')->value('descricao');

    expect(Ncm::query()->where('codigo', '22030000')->value('descricao'))->toBe('Cervejas de malte.')
        ->and($ncmLongo)->toContain('tela')
        ->and(mb_strlen($ncmLongo))->toBeGreaterThan(50);

    $cnae = Cnae::query()->where('codigo', '0111301')->firstOrFail();
    expect($cnae->descricao)->toBe('Cultivo de arroz')
        ->and($cnae->secao)->toBe('A')
        ->and($cnae->divisao)->toBe('01')
        ->and($cnae->classe)->toBe('01113');
});
