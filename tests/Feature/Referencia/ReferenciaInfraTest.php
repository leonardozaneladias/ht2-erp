<?php

declare(strict_types=1);

use App\Enums\Referencia\RegiaoBrasil;
use App\Models\Referencia\Estado;
use Database\Seeders\Referencia\EstadoSeeder;
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

arch('models de Referência não são tenant-scoped (sem BelongsToEmpresa)')
    ->expect('App\Models\Referencia')
    ->not->toUse('App\Models\Concerns\BelongsToEmpresa');
