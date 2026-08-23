<?php

declare(strict_types=1);

use App\Support\Modules\ModuleRegistry;
use Illuminate\Database\Seeder;

/*
|--------------------------------------------------------------------------
| Canal de seeders das extensões
|--------------------------------------------------------------------------
|
| A ordem é a razão de o canal ter dois baldes em vez de uma lista só: os
| seeders de demo do core (empresa, usuário) consomem catálogos de referência,
| então uma extensão que forneça catálogo precisa rodar antes deles.
|
*/

beforeEach(fn () => ModuleRegistry::flush());
afterEach(fn () => ModuleRegistry::flush());

final class SeederFake extends Seeder
{
    public function run(): void {}
}

final class OutroSeederFake extends Seeder
{
    public function run(): void {}
}

it('separa os seeders que rodam antes dos que rodam depois do core', function () {
    ModuleRegistry::seeder(SeederFake::class, antesDoCore: true);
    ModuleRegistry::seeder(OutroSeederFake::class);

    expect(ModuleRegistry::seeders(antesDoCore: true))->toBe([SeederFake::class])
        ->and(ModuleRegistry::seeders())->toBe([OutroSeederFake::class]);
});

it('preserva a ordem de registro dentro de cada balde', function () {
    ModuleRegistry::seeder(SeederFake::class);
    ModuleRegistry::seeder(OutroSeederFake::class);

    expect(ModuleRegistry::seeders())->toBe([SeederFake::class, OutroSeederFake::class]);
});

it('não registra o mesmo seeder duas vezes', function () {
    ModuleRegistry::seeder(SeederFake::class);
    ModuleRegistry::seeder(SeederFake::class);

    expect(ModuleRegistry::seeders())->toHaveCount(1);
});

it('começa vazio e é limpo pelo flush', function () {
    expect(ModuleRegistry::seeders())->toBe([])
        ->and(ModuleRegistry::seeders(antesDoCore: true))->toBe([]);

    ModuleRegistry::seeder(SeederFake::class);
    ModuleRegistry::flush();

    expect(ModuleRegistry::seeders())->toBe([]);
});
