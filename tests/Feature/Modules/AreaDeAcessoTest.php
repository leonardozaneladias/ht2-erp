<?php

declare(strict_types=1);

use HT2ML\Core\Support\Access\AreaDeAcesso;
use HT2ML\Core\Support\Access\PermissionRegistry;
use HT2ML\Core\Support\Modules\ModuleRegistry;

/*
|--------------------------------------------------------------------------
| A gaveta do catálogo de permissões é um conjunto ABERTO
|--------------------------------------------------------------------------
|
| Até aqui a gaveta era o enum ModuloAcesso: 11 casos fechados, quatro `match`
| exaustivos, e ModuleRegistry::permissoes() lançando InvalidArgumentException
| para qualquer outra chave. Um produto com quatro módulos de negócio próprios
| — escola, pedagógico, financeiro, cantina — tinha duas saídas: empilhar ~90
| permissões dentro de 'negocio', onde nenhuma tela de acesso fica navegável,
| ou editar o core. As duas são a doença que a plataforma existe para evitar.
|
| Abrir o CONJUNTO resolve; apagar o TIPO espalharia risco (11 chaves de config,
| 2 telas, 3 blades e 2 testes dependem do enum). Então o enum continua sendo a
| SEMENTE de config('access.areas'), e o VO AreaDeAcesso é o tipo que representa
| qualquer área — venha ela do enum, do config do produto ou de uma extensão.
|
*/

beforeEach(function (): void {
    ModuleRegistry::flush();
    $this->areasOriginais = config('access.areas');
    $this->modulosOriginais = config('access.modules');
});

afterEach(function (): void {
    ModuleRegistry::flush();
    config(['access.areas' => $this->areasOriginais, 'access.modules' => $this->modulosOriginais]);
});

it('semeia as áreas do core a partir do enum, sem redigitar os 11 casos', function (): void {
    $areas = AreaDeAcesso::todas();

    expect(array_keys($areas))->toContain('dashboard', 'usuarios', 'tabelas_auxiliares')
        ->and($areas['tabelas_auxiliares']->label)->toBe('Tabelas Auxiliares')
        ->and($areas['tabelas_auxiliares']->icone)->toBe('tabler--table');
});

it('uma extensão declara a própria área e as permissões entram nela', function (): void {
    ModuleRegistry::areaDeAcesso('escola', 'Escola', 'Cadastros da vida escolar.', 'tabler--school');
    ModuleRegistry::permissoes('escola', ['escola.alunos.listar' => ['label' => 'Listar alunos']]);

    ModuleRegistry::aplicarContribuicoes();

    expect(config('access.modules.escola'))->toHaveKey('escola.alunos.listar')
        ->and(AreaDeAcesso::de('escola')->label)->toBe('Escola')
        ->and(AreaDeAcesso::de('escola')->icone)->toBe('tabler--school');
});

it('a permissão de uma área nova atravessa o catálogo sem explodir no tipo', function (): void {
    ModuleRegistry::areaDeAcesso('escola', 'Escola');
    ModuleRegistry::permissoes('escola', ['escola.alunos.listar' => ['label' => 'Listar alunos']]);
    ModuleRegistry::aplicarContribuicoes();

    $registry = app(PermissionRegistry::class);

    // Antes desta mudança, PermissionDefinitionDTO fazia ModuloAcesso::from() e
    // isto era um ValueError — a tela de acesso inteira caía por causa de uma
    // extensão instalada.
    expect($registry->existe('escola.alunos.listar'))->toBeTrue()
        ->and($registry->areaDe('escola.alunos.listar')?->chave)->toBe('escola')
        ->and($registry->porArea()->keys())->toContain('escola');
});

it('área não declarada degrada para um rótulo legível em vez de quebrar a tela', function (): void {
    $area = AreaDeAcesso::de('modulo_orfao');

    expect($area->chave)->toBe('modulo_orfao')
        ->and($area->label)->toBe('Modulo Orfao')
        ->and($area->declarada)->toBeFalse();
});

it('o produto vence a extensão quando as duas descrevem a mesma área', function (): void {
    config(['access.areas.escola' => ['label' => 'Colégio', 'icone' => 'tabler--book']]);

    ModuleRegistry::areaDeAcesso('escola', 'Escola', icone: 'tabler--school');
    ModuleRegistry::aplicarContribuicoes();

    // Mesma semântica que `label` e `icone` do menu já têm: o declarado pelo
    // pacote é sugestão, a config do produto é a decisão de quem instala.
    expect(AreaDeAcesso::de('escola')->label)->toBe('Colégio')
        ->and(AreaDeAcesso::de('escola')->icone)->toBe('tabler--book');
});
