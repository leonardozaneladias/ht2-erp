<?php

declare(strict_types=1);

use App\Enums\Referencia\TipoCfop;
use App\Models\Referencia\Banco;
use App\Models\Referencia\Cargo;
use App\Models\Referencia\Cfop;
use App\Models\Referencia\Cnae;
use App\Models\Referencia\Moeda;
use App\Models\Referencia\Ncm;
use App\Models\Referencia\TipoLogradouro;
use Database\Seeders\Referencia\BancoSeeder;
use Database\Seeders\Referencia\CargoSeeder;
use Database\Seeders\Referencia\CfopSeeder;
use Database\Seeders\Referencia\CnaeSeeder;
use Database\Seeders\Referencia\MoedaSeeder;
use Database\Seeders\Referencia\NcmSeeder;
use Database\Seeders\Referencia\TipoLogradouroSeeder;
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
