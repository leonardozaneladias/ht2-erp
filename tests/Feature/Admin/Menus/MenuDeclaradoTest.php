<?php

declare(strict_types=1);

use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Ordem e agrupamento declarados no config
|--------------------------------------------------------------------------
|
| Até aqui ordem e grupo existiam SÓ em menu_personalizacoes, e a disposição
| padrão do starter kit era uma Action que hardcodava 'grupo-tab-rh',
| 'ref-cnaes' e 'rh-departamentos' dentro do core — o core conhecendo extensões
| pelo nome. Efeito colateral: toda instalação nova nascia com 23 linhas de
| personalização que nenhum humano escolheu, e a tela de Gestão de Menus
| marcava todas como "personalizado".
|
| Agora cada dono declara a própria disposição, e a semântica é a mesma que
| `label` e `icone` já tinham: o declarado é sugestão, o banco é a decisão de
| quem instalou. A tabela nasce vazia e cada linha volta a significar uma
| decisão humana.
|
*/

beforeEach(function (): void {
    config(['admin-menu' => [
        [
            'key' => 'principal',
            'title' => 'Principal',
            'ordem' => 100,
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'tabler--dashboard', 'route' => 'admin.dashboard'],
            ],
        ],
        [
            'key' => 'gestao',
            'title' => 'Gestão',
            'ordem' => 200,
            'grupos' => [
                'grupo-cadastros' => ['label' => 'Organização', 'icone' => 'tabler--folder', 'ordem' => 100],
                'grupo-seguranca' => ['label' => 'Segurança', 'icone' => 'tabler--shield-lock', 'ordem' => 200],
            ],
            'items' => [
                ['key' => 'auditoria', 'label' => 'Auditoria', 'icon' => 'tabler--history', 'ordem' => 300],
                ['key' => 'empresas', 'label' => 'Empresas', 'icon' => 'tabler--building', 'grupo' => 'grupo-cadastros', 'ordem' => 100],
                ['key' => 'acesso', 'label' => 'Acesso', 'icon' => 'tabler--lock', 'grupo' => 'grupo-seguranca', 'ordem' => 100],
                ['key' => 'usuarios', 'label' => 'Usuários', 'icon' => 'tabler--users', 'grupo' => 'grupo-cadastros', 'ordem' => 200],
            ],
        ],
    ]]);

    $this->service = app(MenuService::class);
    $this->service->invalidarCache();
});

$chaves = fn (array $secoes, string $secao): array => array_column(
    collect($secoes)->firstWhere('key', $secao)['items'] ?? [],
    'key',
);

it('monta grupos e ordem a partir do config, com a tabela vazia', function () use ($chaves): void {
    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);

    expect(MenuPersonalizacao::query()->count())->toBe(0)
        ->and(array_column($secoes, 'key'))->toBe(['principal', 'gestao'])
        ->and($chaves($secoes, 'gestao'))->toBe(['grupo-cadastros', 'grupo-seguranca', 'auditoria']);

    $porKey = collect(collect($secoes)->firstWhere('key', 'gestao')['items'])->keyBy('key');

    expect($porKey['grupo-cadastros']['label'])->toBe('Organização')
        ->and($porKey['grupo-seguranca']['icon'])->toBe('tabler--shield-lock')
        ->and(array_column($porKey['grupo-cadastros']['children'], 'key'))->toBe(['empresas', 'usuarios'])
        ->and(array_column($porKey['grupo-seguranca']['children'], 'key'))->toBe(['acesso']);
});

it('a ordem declarada da seção decide a posição', function (): void {
    config(['admin-menu.0.ordem' => 900]);
    $this->service->invalidarCache();

    expect(array_column($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'key'))
        ->toBe(['gestao', 'principal']);
});

it('o banco vence o declarado — em ordem, rótulo e ícone do grupo', function () use ($chaves): void {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-seguranca',
        'label' => 'Controle', 'icone' => 'tabler--key', 'ordem' => 1,
    ]);
    $this->service->invalidarCache();

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $porKey = collect(collect($secoes)->firstWhere('key', 'gestao')['items'])->keyBy('key');

    expect($chaves($secoes, 'gestao'))->toBe(['grupo-seguranca', 'grupo-cadastros', 'auditoria'])
        ->and($porKey['grupo-seguranca']['label'])->toBe('Controle')
        ->and($porKey['grupo-seguranca']['icon'])->toBe('tabler--key');
});

it('tirar um item do grupo pela tela não é desfeito pelo declarado', function () use ($chaves): void {
    // Pelo caminho real: fabricar a linha à mão provaria só que o MenuService lê
    // o que eu escrevi, e não que arrastar na tela escreve isso.
    app(HT2ML\Core\Actions\Admin\Menu\ReordenarItensMenuAction::class)->execute(
        movidoKey: 'empresas',
        containerDestino: 'secao:gestao',
        ordens: ['secao:gestao' => ['empresas', 'grupo-cadastros', 'grupo-seguranca', 'auditoria']],
    );
    $this->service->invalidarCache();

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $porKey = collect(collect($secoes)->firstWhere('key', 'gestao')['items'])->keyBy('key');

    expect($chaves($secoes, 'gestao'))->toContain('empresas')
        ->and(array_column($porKey['grupo-cadastros']['children'], 'key'))->toBe(['usuarios']);
});

it('renomear o item pela tela não o tira do grupo declarado', function (): void {
    // Personalização SEM decisão de container: só o rótulo mudou.
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'label' => 'Organizações']);
    $this->service->invalidarCache();

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $porKey = collect(collect($secoes)->firstWhere('key', 'gestao')['items'])->keyBy('key');

    expect(array_column($porKey['grupo-cadastros']['children'], 'key'))->toBe(['empresas', 'usuarios'])
        ->and(collect($porKey['grupo-cadastros']['children'])->firstWhere('key', 'empresas')['label'])
        ->toBe('Organizações');
});

it('grupo declarado é destino válido de arraste, mesmo sem linha no banco', function (): void {
    expect($this->service->chavesDeGrupos())->toBe(['grupo-cadastros', 'grupo-seguranca']);
});

it('o padrão de um grupo declarado existe, para a tela normalizar a edição', function (): void {
    expect($this->service->padraoDe(HT2ML\Core\Enums\TipoPersonalizacaoMenu::Grupo, 'grupo-cadastros'))
        ->toBe(['label' => 'Organização', 'icone' => 'tabler--folder'])
        ->and($this->service->padraoDe(HT2ML\Core\Enums\TipoPersonalizacaoMenu::Grupo, 'grupo-da-tela'))
        ->toBeNull();
});

it('grupo declarado some quando nenhum filho é visível ao usuário', function () use ($chaves): void {
    config(['admin-menu.1.items' => [
        ['key' => 'empresas', 'label' => 'Empresas', 'icon' => 'tabler--building', 'grupo' => 'grupo-cadastros', 'permission' => 'empresas.listar'],
    ]]);
    $this->service->invalidarCache();

    // mostrarTudo garante que o grupo APARECE — sem esta metade a assertiva de
    // baixo passaria mesmo se os grupos declarados nunca fossem montados.
    expect($chaves($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'gestao'))
        ->toBe(['grupo-cadastros']);

    // Usuário nulo sem mostrarTudo: nenhuma permissão passa, e grupo é
    // apresentação pura — sem filho visível, some.
    expect($chaves($this->service->estruturaParaSidebar(null), 'gestao'))->toBe([]);
});

it('config nova é cache frio na hora, sem esperar os 600s do TTL', function () use ($chaves): void {
    // Popula o cache.
    expect($chaves($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'gestao'))
        ->not->toContain('escola-alunos');

    // Uma extensão acabou de ser instalada: o config mudou, mas ninguém mexeu na
    // tela de Gestão de Menus — então invalidarCache() não foi chamado, e nada
    // vai chamá-lo. Antes da impressão digital na chave, a sidebar ficava até
    // dez minutos sem os itens recém-instalados.
    config(['admin-menu.1.items' => [
        ...(array) config('admin-menu.1.items'),
        ['key' => 'escola-alunos', 'label' => 'Alunos', 'icon' => 'tabler--user'],
    ]]);

    expect($chaves($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'gestao'))
        ->toContain('escola-alunos');
});
