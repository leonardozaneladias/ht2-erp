<?php

declare(strict_types=1);

use HT2ML\Core\Enums\ModuloAcesso;
use HT2ML\Core\Support\Modules\ModuleRegistry;

/*
|--------------------------------------------------------------------------
| Declaração de permissões e itens de menu pelas extensões
|--------------------------------------------------------------------------
|
| O registry é estático e a declaração vive no boot() do ServiceProvider da
| extensão. Cada nova instância da aplicação no mesmo processo redeclara as
| mesmas contribuições — em teste isso acontece a cada caso. Sem deduplicação
| no registro, a lista de menu cresce sem parar.
|
*/

beforeEach(fn () => ModuleRegistry::flush());
afterEach(fn () => ModuleRegistry::flush());

$item = fn (string $key): array => ['key' => $key, 'label' => ucfirst($key)];

it('não acumula itens de menu quando a extensão redeclara', function () use ($item) {
    foreach (range(1, 5) as $ignorado) {
        ModuleRegistry::itensDeMenu('negocio', [$item('rh-funcionarios'), $item('rh-departamentos')]);
    }

    expect(ModuleRegistry::aplicados('negocio'))->toHaveCount(2);
});

it('acumula itens distintos de extensões diferentes na mesma seção', function () use ($item) {
    ModuleRegistry::itensDeMenu('negocio', [$item('rh-funcionarios')]);
    ModuleRegistry::itensDeMenu('negocio', [$item('financeiro-titulos')]);

    expect(array_column(ModuleRegistry::aplicados('negocio'), 'key'))
        ->toBe(['rh-funcionarios', 'financeiro-titulos']);
});

it('aceita o enum ou a string do módulo de acesso', function () {
    ModuleRegistry::permissoes(ModuloAcesso::TabelasAuxiliares, ['ref.a' => ['label' => 'A']]);
    ModuleRegistry::permissoes('tabelas_auxiliares', ['ref.b' => ['label' => 'B']]);

    ModuleRegistry::aplicarContribuicoes();

    expect(config('access.modules.tabelas_auxiliares'))
        ->toHaveKeys(['ref.a', 'ref.b']);
});

it('recusa módulo de acesso inexistente', function () {
    ModuleRegistry::permissoes('nao-existe', ['x' => ['label' => 'X']]);
})->throws(InvalidArgumentException::class, 'Módulo de acesso desconhecido');
