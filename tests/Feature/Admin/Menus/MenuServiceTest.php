<?php

declare(strict_types=1);

use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['admin-menu' => [
        [
            'key' => 'principal',
            'title' => 'Principal',
            'items' => [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'tabler--dashboard',
                    'route' => 'admin.dashboard',
                    'active' => ['admin.dashboard'],
                ],
            ],
        ],
        [
            'key' => 'gestao',
            'title' => 'Gestão',
            'items' => [
                [
                    'key' => 'empresas',
                    'label' => 'Empresas',
                    'icon' => 'tabler--building-community',
                    'route' => 'admin.empresas.index',
                    'permission' => 'empresas.listar',
                    'active' => ['admin.empresas.*'],
                ],
                [
                    'key' => 'usuarios',
                    'label' => 'Usuários',
                    'icon' => 'tabler--users',
                    'route' => 'admin.usuarios.index',
                    'permission' => 'usuarios.listar',
                    'active' => ['admin.usuarios.*'],
                ],
            ],
        ],
    ]]);

    $this->service = app(MenuService::class);
    $this->service->invalidarCache();
});

function itemDaEstrutura(array $secoes, string $key): ?array
{
    return collect($secoes)
        ->flatMap(fn (array $secao): array => $secao['items'])
        ->firstWhere('key', $key);
}

it('aplica label e ícone personalizados ao item', function () {
    MenuPersonalizacao::create([
        'tipo' => 'item',
        'key' => 'empresas',
        'label' => 'Organizações',
        'icone' => 'tabler--building',
    ]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $item = itemDaEstrutura($secoes, 'empresas');

    expect($item['label'])->toBe('Organizações')
        ->and($item['labelPadrao'])->toBe('Empresas')
        ->and($item['icon'])->toBe('tabler--building')
        ->and($item['iconPadrao'])->toBe('tabler--building-community')
        ->and($item['personalizado'])->toBeTrue();
});

it('ordena itens pela ordem personalizada e joga itens sem ordem para o fim', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'ordem' => 1]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $gestao = collect($secoes)->firstWhere('key', 'gestao');

    expect(array_column($gestao['items'], 'key'))->toBe(['usuarios', 'empresas']);
});

it('move item para outra seção e ignora seção destino inexistente', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'secao_key' => 'principal', 'ordem' => 9]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'secao_key' => 'nao-existe']);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $principal = collect($secoes)->firstWhere('key', 'principal');
    $gestao = collect($secoes)->firstWhere('key', 'gestao');

    // Quem tem ordem personalizada vem antes; itens sem ordem vão para o fim.
    expect(array_column($principal['items'], 'key'))->toBe(['empresas', 'dashboard'])
        ->and(array_column($gestao['items'], 'key'))->toBe(['usuarios'])
        ->and(itemDaEstrutura($secoes, 'empresas')['secaoNaturalKey'])->toBe('gestao');
});

it('renomeia e reordena seções', function () {
    MenuPersonalizacao::create(['tipo' => 'secao', 'key' => 'gestao', 'label' => 'Cadastros', 'ordem' => 1]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);

    expect(array_column($secoes, 'key'))->toBe(['gestao', 'principal'])
        ->and($secoes[0]['title'])->toBe('Cadastros')
        ->and($secoes[0]['titlePadrao'])->toBe('Gestão');
});

it('remove item inativo da sidebar mesmo no preview, mas mantém na gestão', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'dashboard', 'ativo' => false]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);

    // A seção Principal fica vazia e some da sidebar.
    expect(itemDaEstrutura($secoes, 'dashboard'))->toBeNull()
        ->and(array_column($secoes, 'key'))->toBe(['gestao']);

    $gestao = $this->service->estruturaParaGestao();
    $item = itemDaEstrutura($gestao['secoes'], 'dashboard');

    expect($item['ativo'])->toBeFalse();
});

it('ignora personalização órfã na sidebar e a lista na gestão', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'modulo-removido', 'label' => 'Fantasma']);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $gestao = $this->service->estruturaParaGestao();

    expect(itemDaEstrutura($secoes, 'modulo-removido'))->toBeNull()
        ->and($gestao['orfas'])->toHaveCount(1)
        ->and($gestao['orfas']->first()->key)->toBe('modulo-removido');
});

it('filtra itens pela permissão do usuário', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = criarAdminUser();
    $role = criarRoleAdmin('teste-menu', 10);
    $role->givePermissionTo(
        Permission::query()->where('name', 'empresas.listar')->where('guard_name', 'admin')->firstOrFail(),
    );
    $user->assignRole($role);

    $secoes = $this->service->estruturaParaSidebar($user);
    $chaves = collect($secoes)->flatMap(fn (array $secao): array => array_column($secao['items'], 'key'));

    // Dashboard não exige permissão; usuários exige usuarios.listar (não tem).
    expect($chaves->all())->toBe(['dashboard', 'empresas']);
});

it('exige key estável em toda seção e item do config', function () {
    config(['admin-menu' => [['title' => 'Sem Key', 'items' => []]]]);
    $this->service->invalidarCache();

    expect(fn () => $this->service->estruturaParaSidebar(null, mostrarTudo: true))
        ->toThrow(LogicException::class, "sem 'key'");

    config(['admin-menu' => [['key' => 'a', 'title' => 'A', 'items' => [['label' => 'Solto']]]]]);
    $this->service->invalidarCache();

    expect(fn () => $this->service->estruturaParaSidebar(null, mostrarTudo: true))
        ->toThrow(LogicException::class, "sem 'key'");
});

it('cacheia a estrutura mesclada e invalida sob demanda', function () {
    // Primeira chamada popula o cache.
    $this->service->estruturaParaSidebar(null, mostrarTudo: true);

    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'label' => 'Mudou']);

    $aindaCacheado = itemDaEstrutura($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'empresas');
    expect($aindaCacheado['label'])->toBe('Empresas');

    $this->service->invalidarCache();

    $atualizado = itemDaEstrutura($this->service->estruturaParaSidebar(null, mostrarTudo: true), 'empresas');
    expect($atualizado['label'])->toBe('Mudou');
});

it('agrupa itens pelo grupo_key e posiciona o grupo na seção destino', function () {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-pessoas', 'label' => 'Pessoas',
        'icone' => 'tabler--users-group', 'secao_key' => 'gestao', 'ordem' => 1, 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-pessoas', 'ordem' => 1]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'grupo_key' => 'grupo-pessoas', 'ordem' => 2]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $gestao = collect($secoes)->firstWhere('key', 'gestao');
    $grupo = collect($gestao['items'])->firstWhere('key', 'grupo-pessoas');

    expect($grupo['tipo'])->toBe('grupo')
        ->and($grupo['label'])->toBe('Pessoas')
        ->and(array_column($grupo['children'], 'key'))->toBe(['usuarios', 'empresas'])
        ->and(collect($gestao['items'])->firstWhere('key', 'usuarios'))->toBeNull();
});

it('esconde grupo sem filho visível e grupo inativo esconde os filhos', function () {
    $this->seed(RolePermissionSeeder::class);

    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-vazio', 'label' => 'Vazio',
        'secao_key' => 'principal', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-gestao', 'label' => 'Gestão Restrita',
        'secao_key' => 'gestao', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'grupo_key' => 'grupo-gestao']);

    // Grupo sem nenhum filho some até no preview.
    $preview = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    expect(itemDaEstrutura($preview, 'grupo-vazio'))->toBeNull()
        ->and(itemDaEstrutura($preview, 'grupo-gestao'))->not->toBeNull();

    // Usuário sem permissão dos filhos não vê o grupo.
    $semAcesso = criarAdminUser('cego@teste.com');
    $secoes = $this->service->estruturaParaSidebar($semAcesso);
    expect(itemDaEstrutura($secoes, 'grupo-gestao'))->toBeNull();

    // Grupo inativo esconde a si e aos filhos.
    MenuPersonalizacao::query()->where('key', 'grupo-gestao')->update(['ativo' => false]);
    $this->service->invalidarCache();

    $aposInativar = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    expect(itemDaEstrutura($aposInativar, 'grupo-gestao'))->toBeNull()
        ->and(itemDaEstrutura($aposInativar, 'empresas'))->toBeNull();
});

it('aplica fallbacks: grupo com seção inválida vai à primeira e grupo_key morto volta ao natural', function () {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-solto', 'label' => 'Solto',
        'secao_key' => 'nao-existe', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'dashboard', 'grupo_key' => 'grupo-solto']);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'grupo_key' => 'grupo-excluido']);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $principal = collect($secoes)->firstWhere('key', 'principal');
    $grupo = collect($principal['items'])->firstWhere('key', 'grupo-solto');

    // Grupo caiu na 1ª seção; o filho continua dentro dele.
    expect($grupo)->not->toBeNull()
        ->and(array_column($grupo['children'], 'key'))->toBe(['dashboard']);

    // grupo_key apontando para grupo inexistente → item volta à seção natural.
    $gestao = collect($secoes)->firstWhere('key', 'gestao');
    expect(array_column($gestao['items'], 'key'))->toContain('usuarios');
});

it('renderiza seção custom com itens e a esconde quando vazia', function () {
    MenuPersonalizacao::create([
        'tipo' => 'secao', 'key' => 'secao-operacoes', 'label' => 'Operações', 'e_custom' => true,
    ]);

    // Vazia: some da sidebar, aparece na gestão.
    $sidebar = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $gestao = $this->service->estruturaParaGestao();

    expect(collect($sidebar)->firstWhere('key', 'secao-operacoes'))->toBeNull()
        ->and(collect($gestao['secoes'])->firstWhere('key', 'secao-operacoes')['eCustom'])->toBeTrue();

    // Com item movido para ela, renderiza.
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'secao_key' => 'secao-operacoes']);
    $this->service->invalidarCache();

    $comItem = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $secao = collect($comItem)->firstWhere('key', 'secao-operacoes');

    expect($secao['title'])->toBe('Operações')
        ->and(array_column($secao['items'], 'key'))->toBe(['empresas']);
});

it('nunca trata customs como órfãs e expõe os helpers de destino', function () {
    MenuPersonalizacao::create(['tipo' => 'secao', 'key' => 'secao-extra', 'label' => 'Extra', 'e_custom' => true]);
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-extra', 'label' => 'Grupo Extra',
        'secao_key' => 'principal', 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'item-removido', 'label' => 'Órfã Real']);

    $gestao = $this->service->estruturaParaGestao();

    expect($gestao['orfas']->pluck('key')->all())->toBe(['item-removido'])
        ->and($this->service->chavesDeGrupos())->toBe(['grupo-extra'])
        ->and($this->service->destinosDeSecao())->toBe(['principal', 'gestao', 'secao-extra']);
});

it('ordena grupos e itens na mesma sequência dentro da seção', function () {
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-meio', 'label' => 'No Meio',
        'secao_key' => 'gestao', 'ordem' => 2, 'e_custom' => true,
    ]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'ordem' => 1]);
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'ordem' => 3]);

    $secoes = $this->service->estruturaParaSidebar(null, mostrarTudo: true);
    $gestao = collect($secoes)->firstWhere('key', 'gestao');

    // Grupo vazio some da sidebar, então valido pela estrutura de gestão.
    $estrutura = $this->service->estruturaParaGestao();
    $gestaoCompleta = collect($estrutura['secoes'])->firstWhere('key', 'gestao');

    expect(array_column($gestaoCompleta['items'], 'key'))->toBe(['usuarios', 'grupo-meio', 'empresas'])
        ->and(array_column($gestao['items'], 'key'))->toBe(['usuarios', 'empresas']);
});

it('expõe permissão e seção natural do item pelo registro', function () {
    expect($this->service->permissaoDoItem('empresas'))->toBe('empresas.listar')
        ->and($this->service->permissaoDoItem('dashboard'))->toBeNull()
        ->and($this->service->permissaoDoItem('inexistente'))->toBeNull()
        ->and($this->service->secaoNaturalDoItem('empresas'))->toBe('gestao')
        ->and($this->service->secaoNaturalDoItem('inexistente'))->toBeNull()
        ->and($this->service->chavesDeItens())->toBe(['dashboard', 'empresas', 'usuarios'])
        ->and($this->service->chavesDeSecoes())->toBe(['principal', 'gestao']);
});
