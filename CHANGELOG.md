# Changelog

Mantido no padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

> **Ações pós-merge (clientes):** ao trazer um release da base com `make update-base`,
> rode as ações que a entrada indicar. O padrão seguro é
> `php artisan migrate --force && php artisan access:sync && php artisan cache:clear`
> (já incluído no `make update-base`).

## [Unreleased]

### Added

- **Base declarativa de CRUD** (`HT2ML\Core\Livewire\Grid`): uma tabela declara model,
  recurso, rota e uma **lista de `Campo`**, e a base deriva `fields()`, `columns()`,
  `filters()`, a exportação, o eager-load e as regras de validação. Cinco tabelas
  migradas (Bancos, Cargos, Países, NCMs, Departamentos): **411 linhas viraram 59**,
  com os testes existentes intocados. `RecursoIndex` faz o mesmo pelas listagens.
  `RecursoMultiEmpresa` liga as seis composições da dimensão multiempresa de uma vez.
  Fugas graduadas: por campo (`->comColuna()`, `->comFiltro()`, `->paraExportar()`,
  `Campo::personalizado()`), por método (`parent::`) ou não estendendo.
- **Áreas de acesso e seções de menu viraram conjuntos abertos**
  ([ADR-0021](docs/architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)):
  `config('access.areas')` + o VO `AreaDeAcesso`, e os canais
  `ModuleRegistry::areaDeAcesso()`, `::secaoDeMenu()` e `::grupoDeMenu()`. Um produto
  com módulos próprios deixa de ter que empilhar as permissões em `negocio` ou editar
  o core. O enum `ModuloAcesso` continua sendo a semente das onze áreas do core.
- **`php artisan ht2ml:doutor`**: verifica se as contribuições fecham — área existe,
  seção existe, grupo existe, permissão está no catálogo, rota está registrada, ícone
  está na lista curada. Exit 1, e roda no CI. Na primeira execução encontrou sete
  ícones em uso no menu que a tela de Gestão de Menus recusava.
- **Ordem e agrupamento declaráveis no menu**: `ordem` por seção e por item, e um mapa
  `grupos` por seção. O declarado é sugestão; o banco é a decisão de quem instalou.
- Guards **A1** e **A2** (`tests/Arch/CoreNaoConheceExtensaoTest.php`): o core não
  referencia extensão nem por classe nem por **literal de string**
  ([ADR-0022](docs/architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)).
- Helper de snapshot de tabela (`snapshotDaTabela()`) e fixtures versionadas: colunas,
  filtros e cabeçalho de exportação. Os dez `*CrudTest` afirmavam `assertOk()` e os
  verbos do CRUD; nenhum afirmava que uma coluna ou um filtro aparece.

### Changed

- **`AplicarMenuPadraoAction` e `MenuPadraoSeeder` foram apagados.** A disposição
  padrão do menu passa a ser declarada na config de cada dono. Efeito para quem
  instala: `menu_personalizacoes` **nasce vazia** — antes uma instalação nova chegava
  com 23 linhas que nenhum humano tinha escolhido, e a tela de Gestão de Menus marcava
  todas como "personalizado".
- Contribuição inválida deixou de ser recusada **na declaração** e passa a ser
  verificada **na aplicação**, com mensagem que aponta o arquivo e a linha do
  declarante: fatal fora de produção, `Log::error` em produção. Antes, `permissoes()`
  lançava no ato e `itensDeMenu()` descartava a seção inexistente em silêncio.
- O gerador emite tabela e listagem declarativas: os stubs foram de **198 para 92
  linhas**, e os defeitos que ele imprimia deixaram de existir por construção —
  booleano renderizando `0`/`1`, dinheiro em centavos crus, busca textual em cor
  hexadecimal e coluna numérica ou de data sem filtro nenhum.
- `PermissionDefinitionDTO::$modulo` → `$area` (tipo `AreaDeAcesso`);
  `PermissionRegistry::porModulo()`/`moduloDe()` → `porArea()`/`areaDe()`. A coluna
  `permissions.modulo` mantém o nome; o dado é o mesmo.
- A chave do cache do menu carrega uma impressão digital do registro: instalar uma
  extensão não passa por `invalidarCache()`, e sem ela a sidebar ficava até dez
  minutos sem os itens novos.

### Fixed

- **Todo filtro booleano falava inglês.** O default do PowerGrid é `Yes`/`No` e a
  view do filtro os renderiza direto; seis tabelas mostravam isso a usuários
  brasileiros, e o gerador emitia `Filter::boolean()` sem rótulo, de modo que cada
  módulo novo nascia com o mesmo defeito. Guard em
  `tests/Feature/Grid/FiltrosEmPortuguesTest.php`, varrendo todas as tabelas.
- Sete ícones em uso no `config/admin-menu.php` estavam fora de
  `IconesMenu::disponiveis()`: a tela recusava justamente os ícones que o menu já
  usava, então trocar o ícone de "Bancos" era um caminho sem volta.
- `ReordenarItensMenuAction` estourava `ModelNotFoundException` ao reordenar qualquer
  coisa numa seção com grupo declarado (`firstOrFail` num grupo que existe só na
  config), e desfazia a retirada de um item do grupo no render seguinte.

- Estratégia de **instâncias por cliente** via _clone + re-origin_ ([ADR-0016](docs/architecture/adrs/ADR-0016-instancias-por-cliente.md)):
  fluxo bidirecional de atualização (`make update-base` desce; PR de volta sobe), regra
  de ouro de customização aditiva e modelo de consumo "embutido agora → Composer depois".
- Tooling de instâncias: `bin/new-client.sh` (provisiona um cliente de forma aditiva),
  `bin/release-module.sh` (corta release de módulo via `git subtree split` + tag) e
  `bin/update-from-upstream.sh` (traz updates da base no cliente). Targets de Makefile:
  `new-client`, `release-modulo`, `update-base`.
- Target `make setup-client` — setup inicial de uma instância de cliente **sem dados
  demo** (roda `RolePermissionSeeder` em vez de `migrate --seed`; mantém `instalado=false`),
  para que o Setup Wizard (`/admin/setup`) crie a empresa/branding/admin reais. O `make setup`
  (dev) segue semeando demo e pulando o Wizard.

### Changed

- A base passou a ser um **monorepo**: `packages/modulo-*` agora é versionado nela
  (antes `gitignored`/repo aninhado). O módulo desce ao cliente embutido no
  `git merge upstream`; o release o extrai para `erp-module-{slug}` via subtree split.
- `.husky/pre-push`: branch protegida agora é configurável (`.husky/protected-branch`)
  com _opt-out_ local (`.husky/allow-main-push`, gitignored) — usado por clientes.
- `docs/distribuicao-manutencao.md` e `ADR-0015` refinados: "template repo" → "clone +
  re-origin"; URLs apontam para a conta `leonardozaneladias`.

    _Ações pós-merge:_ `php artisan migrate --force && php artisan access:sync && php artisan cache:clear`.

### Fixed

- Sidebar rola até o item de menu ativo ao carregar a página (e após `wire:navigate`):
  os links navegam por full page load e o scroll do SimpleBar resetava a cada clique —
  em menus longos o item da tela atual ficava fora da área visível.
- Espaço do ícone nos inputs com `input-icon-group`: o seletor de padding passou de
  irmão adjacente (`+`) para irmão geral (`~`) — o `altInput` do flatpickr insere o
  input visível depois do original (hidden) e ficava sem o `ps-10` (placeholder
  sobreposto ao ícone do calendário).
- Upstream das correções do laudo 18 do módulo RH nos componentes compartilhados:
  **F9-01** — `x-shared.date-picker`/`date-range-picker` guardam o valor canônico ISO
  no input original e exibem d/m/Y no `altInput` (antes o ISO hidratado era re-parseado
  como d/m/Y: data exibida errada e mês/dia trocados na digitação); **F9-02** —
  o combobox do `select-search` re-sincroniza com o `<select>` nativo hidratado pelo
  Livewire (antes a edição mostrava o placeholder mesmo com valor salvo). Novo
  componente `x-shared.required-indicator` e regressão browser em
  `tests/Browser/Admin/ComponentesFormRegressaoTest.php`.
