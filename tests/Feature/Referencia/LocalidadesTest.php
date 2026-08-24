<?php

declare(strict_types=1);

use Database\Seeders\Referencia\DadosReferenciaSeeder;
use HT2ML\Core\Models\Referencia\Estado;
use HT2ML\Core\Models\Referencia\Municipio;
use HT2ML\Core\Models\Referencia\Pais;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DadosReferenciaSeeder::class);
});

it('semeia países, estados e municípios com as contagens-âncora', function () {
    expect(Pais::count())->toBe(193)
        ->and(Estado::count())->toBe(27)
        ->and(Municipio::count())->toBeGreaterThanOrEqual(5560);
});

it('todo município referencia um estado existente (integridade FK)', function () {
    expect(Municipio::query()->whereDoesntHave('estado')->count())->toBe(0);

    $sp = Estado::query()->where('sigla', 'SP')->firstOrFail();
    expect(Municipio::query()->where('estado_id', $sp->id)->where('nome', 'São Paulo')->exists())->toBeTrue();
});

it('é idempotente: re-seed não duplica municípios nem países', function () {
    $municipios = Municipio::count();
    $paises = Pais::count();

    $this->seed(DadosReferenciaSeeder::class);

    expect(Municipio::count())->toBe($municipios)
        ->and(Pais::count())->toBe($paises);
});
