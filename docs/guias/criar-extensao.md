# Criar uma extensão

Guia de ponta a ponta para criar uma extensão da plataforma no monorepo da base:
decidir o tipo, montar o pacote, ligá-lo ao core pelos canais do `ModuleRegistry`,
testar, passar pelos guards do CI e publicar.

Exemplos reais usados aqui:

- **`ht2ml/extensao-api`** (`packages/extensao-api/`) — uma **biblioteca**: não tem
  tela, não tem módulo, nem depende do core. Ainda em PR em 2026-10-25 (branch
  `feat/extensao-ia`).
- **`ht2ml/extensao-rh`** (`packages/extensao-rh/`) — um **módulo**: área de negócio
  com telas, permissões e menu.

Vocabulário (módulo, recurso, área, seção, extensão): [ADR-0021](../architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md).
A regra que rege tudo: **o core nunca conhece extensão**, nem por classe nem por
literal ([ADR-0022](../architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)). A
extensão declara o que é dela; o core aplica sem saber de quem é.

## 1. Biblioteca ou módulo

Toda extensão é um pacote Composer em `packages/extensao-<nome>/`, com nome
`ht2ml/extensao-<nome>` e namespace `HT2ML\<Nome>\`. O que ela é fica declarado em
`extra.ht2ml` do `composer.json`:

```json
"extra": {
    "laravel": { "providers": ["HT2ML\\Rh\\RhServiceProvider"] },
    "ht2ml": { "tipo": "modulo", "chave": "rh" }
}
```

| `tipo`       | O que é                                                                                          | Exemplos                                                     |
| ------------ | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------ |
| `modulo`     | Área de negócio com superfície administrativa: recursos, telas, permissões, menu. Exige `chave`. | `extensao-rh`, `extensao-fiscal-br`, `extensao-exemplo-demo` |
| `biblioteca` | Código reutilizável sem módulo: serviços, contratos, middleware, comandos. Sem `chave`.          | `extensao-api`, `extensao-ia`, `extensao-documentos`         |
| `core`       | Só o `ht2ml/core`.                                                                               | `packages/core`                                              |

O `tipo` não é decorativo. `tests/Arch/CoerenciaDoModuloTest.php` reprova pacote sem
`extra.ht2ml.tipo`; para `modulo`, confere a `chave` contra o nome do diretório, o
`ModuleRegistry::modulo()`, o namespace de view, o arquivo de config e o prefixo das
permissões. O `ht2ml:doutor` confere a mesma `chave` no produto instalado.
`extra` e não o `type` do Composer porque `type` seleciona _installer_, não documenta.

Na dúvida: se a extensão precisa de item de menu ou de permissão, é módulo.

## 2. Criar a casca

### Módulo: `make:modulo`

```bash
php artisan make:modulo rh
composer require "ht2ml/extensao-rh:@dev"
```

Gera (lido de `MakeModuloCommand` e `packages/core/stubs/extensao/`):

```
packages/extensao-rh/
├── composer.json            # extra.ht2ml {tipo: modulo, chave: rh}; require php, ht2ml/core, illuminate/support
├── config/rh.php            # modulo_acesso, secao_menu, grupos, recursos (+ marcador)
├── routes/admin.php         # incluído dentro do grupo /admin do core (+ marcador)
├── src/RhServiceProvider.php
├── database/migrations/     # vazias, com .gitkeep
├── database/factories/
├── resources/views/
├── tests/
├── README.md
└── .gitignore
```

e acrescenta o path repository `packages/*` ao `composer.json` raiz, se faltar. A
chave vai em kebab-case: ela vira prefixo de permissão, key de seção, namespace de
view e prefixo de rota. Os recursos entram depois, com
`php artisan make:recurso <Nome> --modulo=rh` ([`criar-recurso.md`](../criar-recurso.md)).

### Biblioteca: à mão

Não há gerador de biblioteca — o stub de módulo traria config de menu e marcadores
que uma biblioteca não usa. Monte o mínimo, como a `extensao-api`:

```
packages/extensao-api/
├── composer.json            # extra.ht2ml {tipo: biblioteca}
├── config/api.php           # opcional, publicável
├── database/migrations/     # opcional
├── lang/{pt_BR,en,es}/      # opcional
├── src/ApiServiceProvider.php
├── src/...                  # Support/, Http/Middleware/, Console/, Contracts/
├── tests/Feature/
├── tests/Fixtures/
├── README.md
└── .gitignore
```

Depois, `composer require "ht2ml/extensao-<nome>:@dev"` na raiz.

## 3. O ServiceProvider

### Módulo

O do RH (`packages/extensao-rh/src/RhServiceProvider.php`) é o desenho padrão:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../config/rh.php', 'rh');

    ModuleRegistry::modulo('rh')
        ->label('Recursos Humanos')
        ->icone('tabler--users-group')
        ->deConfig('rh')
        ->rotas(function (): void {
            $rotas = __DIR__ . '/../routes/admin.php';

            if (is_file($rotas)) {
                require $rotas;
            }
        });
}

public function boot(): void
{
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rh');   // namespace = chave
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

    $this->publishes([__DIR__ . '/../config/rh.php' => config_path('rh.php')], 'rh-config');

    \Livewire\Livewire::component('rh.departamentos.index', Livewire\Departamentos\IndexDepartamento::class);
    // ... um por componente
    \Illuminate\Support\Facades\Gate::policy(Models\Departamento::class, Policies\DepartamentoPolicy::class);
    // make:recurso registra os componentes Livewire e as policies do recurso acima desta linha
}
```

Por que cada parte está onde está:

- **O `ModuleRegistry::modulo()` vai no `register()`**, por causa das rotas: o callback
  precisa existir antes de o core carregar `routes/admin.php`. O resto do builder é
  preguiçoso — só é aplicado em `ModuleRegistry::aplicarContribuicoes()`, no boot do
  core —, então a ordem das chamadas não importa.
- **`require` e não `loadRoutesFrom()`**: o callback fica num registry estático que
  sobrevive à troca de instância de app entre testes, e `loadRoutesFrom()` lê
  `$this->app`, que vindo de uma instância descartada estoura
  `Target class [files] does not exist`.
- **Redeclarar a mesma chave devolve o mesmo builder.** Sob `config:cache` o boot roda
  de novo sobre o próprio resultado; dois builders duplicariam o menu.
- **Livewire e Policy registrados à mão**, porque dentro de um pacote não há descoberta
  automática (seção 9).

### Biblioteca

Sem `ModuleRegistry`. O `ApiServiceProvider` faz o que a biblioteca precisa e mais nada:
`mergeConfigFrom` no `register()`; no `boot()`, `loadTranslationsFrom`,
`loadMigrationsFrom`, `publishes(..., 'api-config')`, middlewares (pelo Kernel HTTP,
com `prependMiddlewareToGroup('api', ...)`), alias de middleware, `RateLimiter::for('api', ...)`,
o renderizador de erro no `ExceptionHandler` e `$this->commands([Console\OpenApiCommand::class])`
dentro de `runningInConsole()`.

Os limiters ficam **fora** de qualquer guard de `route:cache`: o cache guarda o nome do
limiter, não o limiter, e sem o registro a cada boot a rota estoura no primeiro request.

## 4. `config/{modulo}.php`

O que a config de um módulo contém, e o que **não** contém. Formato lido por
`ModuloBuilder::deConfig()`:

```php
return [
    'modulo_acesso' => 'negocio',              // área do catálogo de acesso
    'secao_menu' => 'tabelas-auxiliares',      // seção da sidebar

    'grupos' => [
        'grupo-tab-rh' => [
            'secao' => 'tabelas-auxiliares',   // opcional; default: a seção do módulo
            'label' => 'RH',
            'icone' => 'tabler--users-group',
            'ordem' => 500,
        ],
    ],

    'recursos' => [
        'departamentos' => [
            'label' => 'Departamentos',
            'singular' => 'departamento',
            'icone' => 'tabler--folder',
            'grupo' => 'grupo-tab-rh',
            'ordem' => 500,
            // opcionais: 'rota_base' => 'admin.outra.rota', 'sem_lixeira' => true, 'sem_menu' => true
        ],
        // make:recurso insere os recursos do módulo acima desta linha
    ],
];
```

| Chave           | Efeito                                                                                                                     |
| --------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `modulo_acesso` | área onde as permissões entram (`->naArea()`). Ausente: a área com a chave do módulo                                       |
| `secao_menu`    | seção onde os itens entram (`->naSecao()`). Ausente: a seção com a chave do módulo                                         |
| `grupos`        | submenus declarados pelo módulo (`->grupoDeMenu()`)                                                                        |
| `recursos`      | um por recurso (`->recurso()`), com `label`, `singular`, `icone`, `grupo`, `ordem`, `rota_base`, `sem_lixeira`, `sem_menu` |

**Não** estão na config, porque são derivados da chave do módulo e da chave do recurso
pelo `RecursoBuilder`:

| Derivado            | Fórmula                                                                                  | Exemplo                        |
| ------------------- | ---------------------------------------------------------------------------------------- | ------------------------------ |
| Permissões          | `{modulo}.{recurso}.{acao}`                                                              | `rh.departamentos.listar`      |
| Ações               | `listar`, `criar`, `editar`, `deletar` (+ `restaurar`, `excluir_permanente` com lixeira) |                                |
| Key do item de menu | `{modulo}-{recurso}`                                                                     | `rh-departamentos`             |
| Rota do item        | `admin.{modulo}.{recurso}.index` (ou `rota_base`)                                        | `admin.rh.departamentos.index` |
| Permissão do item   | `{modulo}.{recurso}.listar`                                                              |                                |
| `active`            | `admin.{modulo}.{recurso}.*`                                                             |                                |

Escrever qualquer um deles à mão é a chance de divergir — e já divergiu: o gerador
emitia `departamentos.listar` enquanto o catálogo dizia `rh.departamentos.listar`.

A config é **publicável** (`vendor:publish --tag=<chave>-config`) e é o ponto de
customização por cliente: quem instala decide rótulo, ícone, ordem, grupo e onde as
contribuições entram.

Para perguntar a permissão de um recurso em código, sem recalcular a fórmula:
`ModuleRegistry::permissaoDoRecurso('rh', 'departamentos', 'listar')`.

## 5. A API do builder

Assinaturas lidas de `packages/core/src/Support/Modules/` em 2026-10-25.

### `ModuleRegistry` (estático)

| Método                                                                                                                                 | Para quê                                                    |
| -------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| `modulo(string $chave): ModuloBuilder`                                                                                                 | **A forma preferida.** Declara o módulo e devolve o builder |
| `routes(Closure $callback, EscopoDeRota $escopo = EscopoDeRota::Admin): void`                                                          | Canal direto de rotas (o builder usa este)                  |
| `seeder(string $classe, bool $antesDoCore = false): void`                                                                              | Seeder da extensão no `db:seed`                             |
| `catalogoDeReferencia(string $slug, string $seeder): void`                                                                             | Catálogo por CSV: semeia **e** entra no `referencia:sync`   |
| `areaDeAcesso(string $chave, string $label, string $descricao = '', string $icone = 'tabler--box', string $variant = 'neutral'): void` | Área nova no catálogo de acesso                             |
| `permissoes(ModuloAcesso\|string $area, array $permissoes): void`                                                                      | Canal direto de permissões                                  |
| `secaoDeMenu(string $chave, string $titulo, ?int $ordem = null): void`                                                                 | Seção nova na sidebar                                       |
| `grupoDeMenu(string $chave, string $secao, string $label, string $icone = 'tabler--folder', ?int $ordem = null): void`                 | Grupo (submenu) numa seção                                  |
| `itensDeMenu(string $secao, array $itens): void`                                                                                       | Canal direto de itens de menu                               |
| `permissaoDoRecurso(string $modulo, string $recurso, ?string $acao = null): string`                                                    | A permissão de um recurso, perguntada                       |

Os canais diretos (`permissoes()`, `itensDeMenu()`) continuam públicos por um motivo: a
`extensao-fiscal-br` e a `extensao-exemplo-demo` têm permissões sem prefixo de módulo
(`cnaes.listar`, `exemplos.listar`), mantidas porque renomear invalidaria permissões já
atribuídas. Extensão nova usa o builder.

### `ModuloBuilder`

| Método                                                                                                                         | Para quê                                                                |
| ------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------- |
| `label(string $label): self` / `icone(string $icone): self` / `ordem(int $ordem): self`                                        | Apresentação do módulo (usadas pela área e pela seção que ele declarar) |
| `areaDeAcesso(string $descricao = ''): self`                                                                                   | Declara a área com a chave do módulo                                    |
| `secaoDeMenu(): self`                                                                                                          | Declara a seção com a chave do módulo                                   |
| `naArea(string $chave): self` / `naSecao(string $chave): self`                                                                 | Contribui para área/seção que já existe                                 |
| `grupoDeMenu(string $chave, string $label, string $icone = 'tabler--folder', ?int $ordem = null, ?string $secao = null): self` | Declara um grupo                                                        |
| `rotas(string\|Closure $rotas, EscopoDeRota $escopo = EscopoDeRota::Admin): self`                                              | Rotas no escopo dado — **aplicadas na hora**                            |
| `rotasPublicas(string\|Closure $rotas): self`                                                                                  | Atalho para `EscopoDeRota::Publico`                                     |
| `rotasDeWebhook(string\|Closure $rotas): self`                                                                                 | Atalho para `EscopoDeRota::Webhook`                                     |
| `rotasDeApi(string\|Closure $rotas): self`                                                                                     | Atalho para `EscopoDeRota::Api`                                         |
| `recurso(string $chave): RecursoBuilder`                                                                                       | Abre (e já coleta) um recurso                                           |
| `deConfig(string $namespace): self`                                                                                            | Lê `modulo_acesso`, `secao_menu`, `grupos` e `recursos` da config       |

Passar uma `string` a `rotas()` faz `require` do arquivo; uma `Closure` é executada.

### `RecursoBuilder`

`label(string)`, `singular(string)`, `icone(string)`, `ordem(int)`, `noGrupo(string $chave)`,
`rotaBase(string $prefixo)`, `semLixeira()`, `semMenu()`, e `registrar(): ModuloBuilder`
para voltar ao módulo. O recurso já é coletado ao ser aberto por `recurso()`: um builder
abandonado vira um item feio e visível, nunca um recurso que some sem erro.

## 6. Áreas, seções e grupos

- **Área de acesso** — a gaveta da matriz de permissões em `/admin/acesso`. O core traz
  onze, semeadas do enum `ModuloAcesso` em `config('access.areas')`
  (`dashboard`, `empresas`, `usuarios`, `perfis`, `acessos`, `auditoria`, `configuracoes`,
  `notificacoes`, `negocio`, `sistema`, `tabelas_auxiliares`). O conjunto é aberto: o
  módulo declara a sua com `->areaDeAcesso('descrição')`. Uma área que o produto já
  descreve em `config('access.areas')` vence a do pacote.
- **Seção de menu** — gaveta de primeiro nível da sidebar. `->secaoDeMenu()` declara a do
  módulo. Faixas de `ordem`: core 100–499, módulos 500+. A ordem final é a do banco
  quando alguém arrasta o item em Gestão de Menus.
- **Grupo** — submenu dentro de uma seção. O item entra no grupo pela chave `grupo` do
  recurso.

A convenção é 1:1 entre módulo, área e seção, mas é convenção: o RH entra na área
`negocio` e na seção `tabelas-auxiliares`, num grupo próprio.

Tudo é validado em `aplicarContribuicoes()`, não no ato da declaração — no momento em que
uma extensão declara, a config de outra pode ainda não ter sido mesclada. O que não puder
ser aplicado vira `ProblemaDeContribuicao`: fatal fora de produção, `Log::error` em
produção, e listado pelo `ht2ml:doutor` com a origem (arquivo e linha da declaração).

## 7. Rotas: os quatro escopos

`EscopoDeRota` é um enum **fechado**: o escopo diz o que a rota é, e o core decide o que
isso implica. Cada escopo tem um arquivo hospedeiro em `packages/core/routes/`, carregado
sempre pelo `CoreServiceProvider`.

| Escopo    | Atalho no builder  | Prefixo e nome impostos   | Middleware                                                                                                 | Para quê                              |
| --------- | ------------------ | ------------------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| `Admin`   | `rotas()`          | `/admin`, `admin.`        | `web` + o grupo autenticado do painel (login, sessão, conta ativa, inatividade, 2FA, tenant, preferências) | CRUD e telas do painel                |
| `Publico` | `rotasPublicas()`  | nenhum — o módulo escolhe | `web` (sessão, cookies, CSRF), sem autenticação                                                            | Páginas que o público abre            |
| `Webhook` | `rotasDeWebhook()` | `/webhooks`, `webhooks.`  | `throttle:webhooks` (300/min por IP); sem sessão, sem CSRF                                                 | Endpoint chamado por máquina          |
| `Api`     | `rotasDeApi()`     | `/api`, `api.`            | grupo `api` do Laravel; sem sessão, sem CSRF                                                               | API REST; auth e versão são do módulo |

Dentro do arquivo de rotas do módulo, use prefixos e nomes **relativos**: o RH declara
`Route::prefix('rh/departamentos')->name('rh.departamentos.')` e a rota final é
`/admin/rh/departamentos`, `admin.rh.departamentos.index`.

### Webhook com assinatura

O core registra o alias `webhook.assinatura` (`ValidarAssinaturaWebhook`): HMAC-SHA256
do **corpo bruto** da requisição. O primeiro parâmetro é a **chave de config** que guarda
o segredo, nunca o segredo; o segundo, opcional, é o header (default `X-Webhook-Signature`).

```php
// no ServiceProvider
ModuleRegistry::modulo('financeiro')->rotasDeWebhook(__DIR__ . '/../routes/webhook.php');

// routes/webhook.php do módulo → POST /webhooks/pagamentos, name webhooks.pagamentos
Route::post('/pagamentos', RecebePagamento::class)
    ->middleware('webhook.assinatura:financeiro.webhook_secret')
    ->name('pagamentos');

// header próprio do provedor:
//  ->middleware('webhook.assinatura:pagamentos.segredo,X-Hub-Signature-256')
```

Sem segredo configurado, o endpoint responde **503** a tudo e reporta a exceção (não
valida contra vazio). Assinatura ausente ou errada: **401**. O prefixo `sha256=` (estilo
GitHub) é aceito.

### API

```php
ModuleRegistry::modulo('loja')->rotasDeApi(__DIR__ . '/../routes/api.php');

// routes/api.php do módulo — a versão e o guard são do módulo
Route::prefix('v1')->middleware(['auth:sanctum', 'api.principal-ativo'])->group(function () {
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
});
```

O core não instala o Sanctum nem escolhe guard. Envelope de resposta, paginação, idioma,
erros, limiter `api` e tokens vêm da biblioteca `ht2ml/extensao-api`
(`packages/extensao-api/README.md`).

> **Versão do core.** `EscopoDeRota::Api` e `rotasDeApi()` existem a partir do core
> v0.5.0. A v0.4.x só tem `Admin`, `Publico` e `Webhook`.

Um escopo com rotas e sem hospedeiro — um módulo novo num core antigo — vira 404 sem nada
no log. O `ht2ml:doutor` acusa como "escopo sem hospedeiro".

## 8. Seeders e catálogos

```php
// no register()
ModuleRegistry::seeder(Database\Seeders\PermissoesDoGestorSeeder::class);            // depois do core
ModuleRegistry::seeder(Database\Seeders\TabelaDeApoioSeeder::class, antesDoCore: true); // antes
```

O `DatabaseSeeder` do app roda, nesta ordem: os catálogos de referência, os seeders
registrados com `antesDoCore: true`, os seeders do core (`RolePermissionSeeder`,
`EmpresaSeeder`, `AdminUserSeeder`) e os demais seeders de extensão. Use `antesDoCore`
para dados que os seeders do core consomem; o padrão (depois) encontra empresas, perfis
e usuários no lugar. A `extensao-exemplo-demo` usa o padrão para dar `exemplos.listar` ao
perfil `gestor` — o core não pode citar permissão de extensão.

Catálogo mantido por CSV usa o outro canal:

```php
ModuleRegistry::catalogoDeReferencia('cnaes', Database\Seeders\CnaeSeeder::class);
```

Além de semear no `db:seed`, o catálogo entra na lista do `referencia:sync`, que é passo
de deploy. O seeder estende `HT2ML\Core\Support\Referencia\CsvReferenceSeeder`. O CSV
viaja dentro do pacote, e o seeder **precisa** sobrescrever `caminhoArquivo()`: o default
da base é `database_path('data/referencia/...')`, que aponta para o app e só funciona por
acaso no monorepo. A `extensao-fiscal-br` faz isso numa base própria
(`database/seeders/CsvFiscalSeeder.php`, `return __DIR__ . '/../data/' . $this->arquivo();`)
e guarda os CSV em `database/data/`.

## 9. As descobertas automáticas que morrem num pacote

O Laravel e o Livewire acham sozinhos várias coisas em `app/`. Dentro de um pacote,
nada disso acontece — e a falha é silenciosa. Detalhes e histórico em
[`superficie-do-core.md`](../superficie-do-core.md).

| O que               | Em `app/`                                  | No pacote, declare                                                                                           |
| ------------------- | ------------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| Policy              | `App\Models\X` → `App\Policies\XPolicy`    | `Gate::policy(X::class, XPolicy::class)` no `boot()`                                                         |
| Comando artisan     | `app/Console/Commands`                     | `$this->commands([...])` no `boot()`, dentro de `runningInConsole()`                                         |
| Listener            | `app/Listeners`                            | `Event::listen(Evento::class, Listener::class)`                                                              |
| Componente Livewire | alias por namespace `App\Livewire`         | `Livewire::component('alias', Classe::class)` ou `Livewire::addLocation(classNamespace: 'HT2ML\X\Livewire')` |
| Factory do model    | `Database\Factories\XFactory`              | `protected static function newFactory()` no model (o gerador já escreve)                                     |
| Classe de settings  | `auto_discover_settings` em `app/Settings` | não há canal; ver [`configuracoes-sistema.md`](../configuracoes-sistema.md)                                  |

Pergunta para qualquer coisa nova no pacote: _o framework encontrava isso sozinho?_ Se
sim, agora é preciso declarar.

## 10. O que a extensão pode usar do core

Só o que está em `ht2ml/core`, e declarando `ht2ml/core` no `require`. A lista medida do
que as extensões usam hoje está em [`superficie-do-core.md`](../superficie-do-core.md).
Nunca `App\...`: o app hospedeiro não existe fora do monorepo.

Uma extensão **não** usa classe de outra extensão sem declará-la no `composer.json` — o
`composer-dependency-analyser` acusa (ver seção 12). Uma biblioteca que não precisa do
core, como a `extensao-api`, não o declara: funciona em qualquer app Laravel.

## 11. Testes

Os testes da extensão vivem em `packages/<pacote>/tests/` e rodam na suíte **`Extensoes`**
do `phpunit.xml` da raiz (`<directory>packages/*/tests</directory>`). O `tests/Pest.php` da
raiz estende o `Tests\TestCase` do app para `packages/`, aplica o "sistema instalado" e o
`withoutVite()` — o teste da extensão boota a aplicação inteira do monorepo, com o core e
as outras extensões.

```bash
php artisan test --testsuite=Extensoes
php artisan test packages/extensao-rh/tests
```

**Fixtures:** o `autoload-dev` do pacote (`HT2ML\Api\Tests\`) **não** está no autoloader
da raiz, que só conhece `Tests\`. Carregue a fixture explicitamente no topo do teste:

```php
require_once __DIR__ . '/../Fixtures/UsuarioDeApi.php';
```

É o que `packages/extensao-api/tests/Feature/ApiHttpTest.php` faz.

O `make:recurso` gera o teste Feature de cada recurso em
`packages/<pacote>/tests/Feature/<Plural>/<Nome>CrudTest.php`. Suítes, guards e como rodar:
[`testes-e-guardas.md`](../testes-e-guardas.md).

## 12. As listas manuais: o que editar a cada extensão nova

Boa parte dos guards **deriva** a lista de pacotes sozinha (strict_types, Auditavel, A1,
A2, coerência do módulo). Algumas listas, não. Medido no diff da branch
`feat/extensao-ia` contra a `main` em 2026-10-25 (a extensão `api` e a `ia` exigiram
estas edições):

| Arquivo                               | O que acrescentar                                                                                                                                                                                             | Se esquecer                                                                         |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `composer.json` (raiz)                | `"ht2ml/extensao-<nome>": "@dev"` no `require` (o `composer require` faz)                                                                                                                                     | o pacote não é instalado no monorepo                                                |
| `.github/workflows/ci.yml`            | o diretório no `for p in core extensao-rh ...` do passo "Cada pacote declara o que usa"                                                                                                                       | o pacote nunca passa pelo analisador de dependências                                |
| `composer-dependency-analyser.php`    | `'ht2ml/extensao-<nome>' => 'packages/extensao-<nome>'` no mapa de "o pacote referencia as próprias classes"; e um ignore justificado se a extensão usa dependência só por nome (o Sanctum da `extensao-api`) | o analisador acusa o pacote de usar as próprias classes sem declarar                |
| `phpstan.neon`                        | `packages/extensao-<nome>/src/` em `paths` e `packages/extensao-<nome>/database/migrations` em `databaseMigrationsPath`                                                                                       | o PHPStan não analisa o pacote, e o Larastan não conhece as colunas dos models dele |
| `bin/release-skeleton.sh`             | o slug na lista do `foreach` dos repositórios VCS e uma linha `composer require` no README gerado                                                                                                             | o produto novo não enxerga a extensão                                               |
| `bin/checar-referencias-relativas.py` | `packages/extensao-<nome>/src` (e `database/seeders` ou `database/factories`, se houver) em `BASES`                                                                                                           | referência relativa quebrada no pacote passa despercebida                           |
| `tests/Arch/PlataformaTest.php`       | **só se** a extensão tem model sem `Auditavel`: a classe no `->ignoring([...])`, com o motivo                                                                                                                 | o arch test reprova o model                                                         |

**Se a extensão é `modulo`**, mais duas meta-checagens com contagem fixa:

| Arquivo                                          | O que mudar                                                        |
| ------------------------------------------------ | ------------------------------------------------------------------ |
| `tests/Arch/CoerenciaDoModuloTest.php`           | `expect(modulosDeclarados())->toHaveCount(3)` → 4                  |
| `tests/Feature/Core/MarcadoresDoGeradorTest.php` | `expect(marcadoresDePacote())->toHaveCount(9)` → 12 (3 por módulo) |

As contagens existem de propósito: um glob errado deixaria o dataset vazio e o teste
passaria sem abrir um arquivo.

Depois de editar, rode localmente o que o CI roda: `./vendor/bin/pint --test`,
`./vendor/bin/phpstan analyse`, o analisador para o pacote novo e
`php artisan test --testsuite=Unit,Feature,Extensoes,Arch`.

## 13. Publicar

Uma vez, antes do primeiro release:

```bash
gh repo create leonardozaneladias/ht2ml-extensao-<nome> --private
```

Depois do merge na `main`, a cada versão:

```bash
./bin/release-module.sh extensao-<nome> v0.1.0 --dry-run
./bin/release-module.sh extensao-<nome> v0.1.0     # ou: make release-modulo pacote=extensao-<nome> versao=v0.1.0
```

Se a extensão depende de algo do core que ainda não foi publicado, publique o core antes.
E só corte um release novo do skeleton depois que o repositório da extensão existir com
tag — o skeleton passa a declará-lo. Runbook completo: [`devops/release.md`](../devops/release.md).

Num produto:

```bash
composer require ht2ml/extensao-<nome>
php artisan migrate && php artisan access:sync && php artisan ht2ml:doutor
```

## Checklist

- [ ] `extra.ht2ml.tipo` declarado (`modulo` com `chave`, ou `biblioteca`)
- [ ] ServiceProvider em `extra.laravel.providers`
- [ ] Módulo: `ModuleRegistry::modulo('<chave>')` no `register()`, views com namespace `<chave>`, `config/<chave>.php`
- [ ] Policies, comandos, listeners e componentes Livewire registrados à mão
- [ ] Todo model com `Auditavel` (ou na whitelist, com motivo)
- [ ] `composer.json` declara tudo o que o pacote usa
- [ ] Testes em `packages/<pacote>/tests`, fixtures com `require_once`
- [ ] As listas manuais da seção 12 editadas
- [ ] `php artisan ht2ml:doutor` sai com 0
- [ ] README do pacote diz o que muda ao instalar e o que o produto precisa fazer
- [ ] Repositório criado e primeiro release cortado
