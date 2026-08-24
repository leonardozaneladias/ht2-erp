<?php

declare(strict_types=1);

use Database\Seeders\Referencia\EstadoSeeder;
use HT2ML\Core\Enums\Referencia\RegiaoBrasil;
use HT2ML\Core\Models\Referencia\Estado;
use HT2ML\Core\Models\Referencia\Municipio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('semeia os 27 estados com a região derivada do código IBGE', function () {
    $this->seed(EstadoSeeder::class);

    expect(Estado::count())->toBe(27);

    $sp = Estado::query()->where('sigla', 'SP')->firstOrFail();
    expect($sp->codigo_ibge)->toBe('35')
        ->and($sp->regiao)->toBe(RegiaoBrasil::Sudeste);

    expect(Estado::query()->where('sigla', 'DF')->firstOrFail()->regiao)->toBe(RegiaoBrasil::CentroOeste)
        ->and(Estado::query()->where('sigla', 'AM')->firstOrFail()->regiao)->toBe(RegiaoBrasil::Norte);
});

it('é idempotente: re-seed atualiza in-place, não duplica', function () {
    $this->seed(EstadoSeeder::class);
    $this->seed(EstadoSeeder::class);

    expect(Estado::count())->toBe(27);
});

it('referencia:sync --dry-run não grava', function () {
    $this->artisan('referencia:sync', ['--dry-run' => true])->assertSuccessful();

    expect(Estado::count())->toBe(0);
});

it('referencia:sync popula e registra a atividade', function () {
    $this->artisan('referencia:sync')->assertSuccessful();

    expect(Estado::count())->toBe(27)
        ->and(Activity::query()->where('log_name', 'referencia')->exists())->toBeTrue();
});

it('referencia:sync com argumento de conjunto popula só o conjunto pedido', function () {
    $this->artisan('referencia:sync', ['conjunto' => ['estados']])->assertSuccessful();

    expect(Estado::count())->toBe(27)
        ->and(Municipio::count())->toBe(0);
});

it('referencia:sync com conjunto inexistente falha (FAILURE)', function () {
    $this->artisan('referencia:sync', ['conjunto' => ['inexistente']])
        ->expectsOutputToContain('Nenhum conjunto reconhecido')
        ->assertFailed();
});

arch('models de Referência não são tenant-scoped (sem BelongsToEmpresa)')
    ->expect('App\Models\Referencia')
    ->not->toUse('HT2ML\Core\Models\Concerns\BelongsToEmpresa');
