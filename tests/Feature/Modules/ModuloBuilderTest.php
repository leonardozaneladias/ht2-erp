<?php

declare(strict_types=1);

use HT2ML\Core\Support\Access\AreaDeAcesso;
use HT2ML\Core\Support\Modules\ModuleRegistry;

/*
|--------------------------------------------------------------------------
| Uma declaração, cinco convenções derivadas
|--------------------------------------------------------------------------
|
| Os canais do ModuleRegistry recebem strings soltas e não têm como saber que
| três delas pertencem à mesma coisa. Por isso a permissão de listagem já foi
| escrita por DUAS fórmulas que discordavam entre si — `departamentos.listar`
| de um lado, `rh.departamentos.listar` do outro —, e o efeito foi um gate
| negando em silêncio, sem ninguém ter onde olhar para saber qual estava certa.
|
| `recurso('alunos')->registrar()` resolve mecanicamente as cinco convenções a
| partir de UM dado. A partir daqui, o componente pergunta ao registry qual é
| sua permissão em vez de recalculá-la — e uma segunda fórmula deixa de ser
| possível, em vez de ser proibida.
|
*/

beforeEach(function (): void {
    ModuleRegistry::flush();
    $this->areasOriginais = config('access.areas');
    $this->modulosOriginais = config('access.modules');
    $this->menuOriginal = config('admin-menu');
});

afterEach(function (): void {
    ModuleRegistry::flush();
    config([
        'access.areas' => $this->areasOriginais,
        'access.modules' => $this->modulosOriginais,
        'admin-menu' => $this->menuOriginal,
    ]);
});

it('um módulo declara a si mesmo e a área e a seção nascem juntas', function (): void {
    ModuleRegistry::modulo('escola')
        ->label('Escola')
        ->icone('tabler--school')
        ->ordem(500)
        ->areaDeAcesso('Cadastros da vida escolar.')
        ->secaoDeMenu();

    ModuleRegistry::aplicarContribuicoes();

    $area = AreaDeAcesso::de('escola');
    $secao = collect((array) config('admin-menu'))->firstWhere('key', 'escola');

    expect($area->label)->toBe('Escola')
        ->and($area->descricao)->toBe('Cadastros da vida escolar.')
        ->and($area->icone)->toBe('tabler--school')
        ->and($secao)->not->toBeNull()
        ->and($secao['title'])->toBe('Escola')
        ->and($secao['ordem'])->toBe(500);
});

it('um recurso deriva permissões, item de menu, rota e padrão de active', function (): void {
    ModuleRegistry::modulo('escola')->label('Escola')->areaDeAcesso()->secaoDeMenu()
        ->recurso('alunos')->label('Alunos')->icone('tabler--user')->ordem(100)->registrar();

    ModuleRegistry::aplicarContribuicoes();

    /** @var array<string, mixed> $permissoes */
    $permissoes = (array) config('access.modules.escola');

    expect(array_keys($permissoes))->toBe([
        'escola.alunos.listar',
        'escola.alunos.criar',
        'escola.alunos.editar',
        'escola.alunos.deletar',
        'escola.alunos.restaurar',
        'escola.alunos.excluir_permanente',
    ])->and($permissoes['escola.alunos.listar']['label'])->toBe('Listar alunos');

    $item = collect(collect((array) config('admin-menu'))->firstWhere('key', 'escola')['items'])
        ->firstWhere('key', 'escola-alunos');

    expect($item)->not->toBeNull()
        ->and($item['label'])->toBe('Alunos')
        ->and($item['icon'])->toBe('tabler--user')
        ->and($item['route'])->toBe('admin.escola.alunos.index')
        ->and($item['permission'])->toBe('escola.alunos.listar')
        ->and($item['active'])->toBe(['admin.escola.alunos.*'])
        ->and($item['ordem'])->toBe(100);
});

it('o componente pergunta ao registry, em vez de recalcular a permissão', function (): void {
    ModuleRegistry::modulo('escola')->label('Escola')->areaDeAcesso()->secaoDeMenu()
        ->recurso('alunos')->label('Alunos')->registrar();

    // Esta é a costura que mata a classe de bug: uma fonte, consultável.
    expect(ModuleRegistry::permissaoDoRecurso('escola', 'alunos', 'listar'))
        ->toBe('escola.alunos.listar')
        ->and(ModuleRegistry::permissaoDoRecurso('escola', 'alunos'))
        ->toBe('escola.alunos');
});

it('recurso sem lixeira não ganha restaurar nem excluir permanente', function (): void {
    ModuleRegistry::modulo('escola')->label('Escola')->areaDeAcesso()->secaoDeMenu()
        ->recurso('avisos')->label('Avisos')->semLixeira()->registrar();

    ModuleRegistry::aplicarContribuicoes();

    $permissoes = (array) config('access.modules.escola');

    expect(array_keys($permissoes))->toBe([
        'escola.avisos.listar',
        'escola.avisos.criar',
        'escola.avisos.editar',
        'escola.avisos.deletar',
    ])->and($permissoes['escola.avisos.deletar']['descricao'])->toBe('Remover avisos.');
});

it('o recurso entra no grupo declarado pelo módulo', function (): void {
    ModuleRegistry::modulo('escola')->label('Escola')->areaDeAcesso()->secaoDeMenu()
        ->grupoDeMenu('escola-cadastros', 'Cadastros', 'tabler--folder', 100)
        ->recurso('alunos')->label('Alunos')->noGrupo('escola-cadastros')->registrar();

    ModuleRegistry::aplicarContribuicoes();

    $secao = collect((array) config('admin-menu'))->firstWhere('key', 'escola');
    $item = collect($secao['items'])->firstWhere('key', 'escola-alunos');

    expect($secao['grupos'])->toHaveKey('escola-cadastros')
        ->and($item['grupo'])->toBe('escola-cadastros');
});

it('o módulo pode apontar para área e seção de outro dono', function (): void {
    // A extensão fiscal contribui para 'tabelas_auxiliares', que é do core e
    // atravessa pacotes por natureza — a convenção 1:1 é convenção, não invariante.
    ModuleRegistry::modulo('fiscal')->label('Fiscal')
        ->naArea('tabelas_auxiliares')
        ->naSecao('tabelas-auxiliares')
        ->recurso('cnaes')->label('CNAEs')->rotaBase('admin.referencia.cnaes')->registrar();

    ModuleRegistry::aplicarContribuicoes();

    expect(config('access.modules.tabelas_auxiliares'))->toHaveKey('fiscal.cnaes.listar')
        ->and(config('access.modules.fiscal'))->toBeNull();

    $item = collect(collect((array) config('admin-menu'))->firstWhere('key', 'tabelas-auxiliares')['items'])
        ->firstWhere('key', 'fiscal-cnaes');

    expect($item['route'])->toBe('admin.referencia.cnaes.index')
        ->and($item['active'])->toBe(['admin.referencia.cnaes.*']);
});

it('declarar duas vezes não duplica — o boot roda de novo sob config:cache', function (): void {
    foreach (range(1, 3) as $ignorado) {
        ModuleRegistry::flush();
        ModuleRegistry::modulo('escola')->label('Escola')->areaDeAcesso()->secaoDeMenu()
            ->recurso('alunos')->label('Alunos')->registrar();
        ModuleRegistry::aplicarContribuicoes();
    }

    $secao = collect((array) config('admin-menu'))->firstWhere('key', 'escola');

    expect($secao['items'])->toHaveCount(1)
        ->and(config('access.modules.escola'))->toHaveCount(6)
        // array_replace, não merge: 'label' => 'X' não pode virar ['X','X','X'].
        ->and(config('access.modules.escola')['escola.alunos.listar']['label'])->toBe('Listar alunos');
});
