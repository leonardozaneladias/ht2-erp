<?php

declare(strict_types=1);

use HT2ML\Core\Enums\ModuloAcesso;
use HT2ML\Core\Exceptions\ContribuicoesInvalidas;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Log;

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

it('declara uma seção nova da sidebar em vez de exigir que ela já exista', function () use ($item) {
    ModuleRegistry::secaoDeMenu('escola', 'Escola', ordem: 500);
    ModuleRegistry::itensDeMenu('escola', [$item('escola-alunos')]);

    ModuleRegistry::aplicarContribuicoes();

    $secao = collect((array) config('admin-menu'))->firstWhere('key', 'escola');

    expect($secao)->not->toBeNull()
        ->and($secao['title'])->toBe('Escola')
        ->and($secao['ordem'])->toBe(500)
        ->and(array_column($secao['items'], 'key'))->toBe(['escola-alunos']);
});

it('declara um grupo dentro da seção', function () {
    ModuleRegistry::grupoDeMenu('grupo-rh', 'tabelas-auxiliares', 'RH', 'tabler--users-group', ordem: 500);

    ModuleRegistry::aplicarContribuicoes();

    $secao = collect((array) config('admin-menu'))->firstWhere('key', 'tabelas-auxiliares');

    expect($secao['grupos'] ?? [])->toHaveKey('grupo-rh')
        ->and($secao['grupos']['grupo-rh']['label'])->toBe('RH');
});

/*
|--------------------------------------------------------------------------
| Falha: nunca silenciosa, nunca fatal em produção, sempre fatal no CI
|--------------------------------------------------------------------------
|
| A causa raiz da assimetria antiga era temporal: permissoes() validava no ATO
| DA DECLARAÇÃO e lançava; itensDeMenu() não validava, e a seção inexistente era
| descartada por um `continue` NA APLICAÇÃO. Os dois estavam errados — no ato da
| declaração a config de outra extensão pode ainda não ter sido mesclada.
|
*/

it('a área inexistente reprova na aplicação, e a mensagem diz o que fazer', function () {
    ModuleRegistry::permissoes('nao-existe', [
        'x.listar' => ['label' => 'X'],
        'x.criar' => ['label' => 'X'],
    ]);

    // Na declaração, nada acontece: a área pode ser declarada por um provider
    // que ainda não bootou.
    expect(true)->toBeTrue();

    ModuleRegistry::aplicarContribuicoes();
})->throws(
    ContribuicoesInvalidas::class,
    "Área de acesso inexistente: 'nao-existe'",
);

it('a seção de menu inexistente deixou de ser descartada em silêncio', function () use ($item) {
    ModuleRegistry::itensDeMenu('secao-fantasma', [$item('escola-alunos')]);

    ModuleRegistry::aplicarContribuicoes();
})->throws(
    ContribuicoesInvalidas::class,
    "Seção de menu inexistente: 'secao-fantasma'",
);

it('o problema aponta o arquivo que declarou', function () {
    ModuleRegistry::permissoes('nao-existe', ['x.listar' => ['label' => 'X']]);

    try {
        ModuleRegistry::aplicarContribuicoes();
    } catch (ContribuicoesInvalidas $e) {
        expect($e->problemas)->toHaveCount(1)
            ->and($e->problemas[0]->canal)->toBe('permissoes')
            ->and($e->problemas[0]->alvo)->toBe('nao-existe')
            // Sem isto, "área inexistente: 'escola'" manda procurar em todos os
            // pacotes instalados.
            ->and($e->problemas[0]->origem)->toBe('tests/Unit/Modules/ModuleRegistryContribuicoesTest.php:116');

        return;
    }

    $this->fail('esperava ContribuicoesInvalidas');
});

it('em produção registra e segue, em vez de derrubar o deploy', function () {
    Log::spy();
    app()->detectEnvironment(fn (): string => 'production');

    ModuleRegistry::permissoes('nao-existe', ['x.listar' => ['label' => 'X']]);
    ModuleRegistry::permissoes('tabelas_auxiliares', ['ref.valida' => ['label' => 'Válida']]);

    ModuleRegistry::aplicarContribuicoes();

    // A contribuição inválida foi pulada; a válida, aplicada.
    expect(config('access.modules.nao-existe'))->toBeNull()
        ->and(config('access.modules.tabelas_auxiliares'))->toHaveKey('ref.valida');

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $mensagem): bool => str_contains($mensagem, "Área de acesso inexistente: 'nao-existe'"))
        ->once();
});
