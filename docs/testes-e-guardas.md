# Testes e guardas de arquitetura

Como a base se testa, o que cada suíte cobre, e quais regras de arquitetura são
executáveis — as que reprovam o CI em vez de depender de alguém lembrar. Lido de
`phpunit.xml`, `tests/Pest.php`, `tests/Arch/*`, `.github/workflows/ci.yml` e
`composer-dependency-analyser.php` em 2026-10-25. O CI em detalhe está em
[`devops/ci-cd.md`](devops/ci-cd.md).

## Suítes

Declaradas no `phpunit.xml` da raiz:

| Suíte       | Diretório          | O que tem                                                               |
| ----------- | ------------------ | ----------------------------------------------------------------------- |
| `Unit`      | `tests/Unit`       | Unidades sem HTTP: gerador, `Campo`, casts, regras, registro de módulos |
| `Feature`   | `tests/Feature`    | Telas, middleware, rotas, comandos, o core instalado no app             |
| `Extensoes` | `packages/*/tests` | Os testes que vivem dentro de cada pacote                               |
| `Arch`      | `tests/Arch`       | Regras de arquitetura (Pest Arch + testes sobre o código-fonte)         |
| `Browser`   | `tests/Browser`    | Navegador real (Pest + Playwright), grupo `browser`                     |

O `tests/Pest.php` estende o `Tests\TestCase` para `Feature`, `Unit`, `Arch`, `Browser` e
`packages/`. Em `Feature`, `Browser` e `packages/`, cada teste começa com o sistema marcado
como instalado (pula o Setup Wizard); em `Feature` e `packages/`, sem Vite. A suíte `Arch`
é suíte própria porque `tests/Arch.php`, na raiz de `tests/`, não era coberto por suíte
nenhuma — e por isso nunca rodou.

## Como rodar

```bash
make test                         # DDEV: php artisan test --parallel --testsuite=Unit,Feature,Extensoes,Arch
php artisan test --testsuite=Arch # uma suíte
php artisan test packages/extensao-rh/tests   # um pacote
npm run test                      # php artisan test --exclude-group=browser
make test-e2e                     # no HOST: npm run build && ./vendor/bin/pest --group=browser
npm run quality                   # Pint --test, ESLint, Prettier --check, PHPStan e os testes
```

`make test-e2e` roda no host, não no container: o Chromium do Playwright vive no macOS,
e o build do Vite é obrigatório porque o layout usa `@vite`.

## Banco: SQLite nos testes, PostgreSQL no DDEV

O `phpunit.xml` define `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:` (e cache, fila e
sessão em memória). Os testes rodam em SQLite **no CI e na máquina local**, inclusive
dentro do DDEV. O app de desenvolvimento roda em **PostgreSQL 16** (`.ddev/config.yaml`),
e é o banco de produção esperado.

Duas consequências:

- Comportamento específico do PostgreSQL (tipos `jsonb`, `ILIKE`, ordenação com
  acento, constraints que o SQLite aceita de outro jeito) **não** é coberto pela suíte.
  Migration nova com recurso de PostgreSQL precisa de conferência manual no DDEV
  (`make fresh`).
- Os `<env>` do `phpunit.xml` não têm `force`, então não sobrescrevem uma variável já
  exportada no shell. Quem exporta `DB_CONNECTION` muda o banco dos testes sem perceber.

O job `core-sozinho` do CI usa SQLite em arquivo (`database/database.sqlite`).

## Guardas de arquitetura (`tests/Arch/`)

### A1 e A2 — o core não conhece extensão (`CoreNaoConheceExtensaoTest.php`)

A dependência é de mão única ([ADR-0022](architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)).
São dois guards porque há duas formas de violar, e só uma é visível a um arch test de
namespace.

- **A1 — referência de classe.** `arch()->expect('HT2ML\Core')->not->toUse(<namespaces das extensões>)`.
  Os namespaces das extensões são derivados dos `composer.json` de `packages/*`.
- **A2 — literal de string.** O core não cita chave que só uma extensão conhece. As chaves
  proibidas são derivadas das configs das extensões: permissões e itens de menu escritos à
  mão, grupos, e — no formato do `ModuloBuilder` — as seis permissões
  `{modulo}.{recurso}.{acao}` e a key `{modulo}-{recurso}` de cada recurso. Tira-se delas
  tudo que o próprio core declara. O guard procura essas chaves:
    - como literal (`T_CONSTANT_ENCAPSED_STRING`, via `token_get_all`, não grep) em
      `packages/core/src`, `packages/core/database`, `packages/core/config` e
      `packages/core/routes`;
    - nas **views** do core (`packages/core/resources/views/**/*.blade.php`): namespace PHP
      de extensão (`HT2ML\Rh\…`), namespace de view de extensão (`'rh::'`) e as chaves acima
      entre aspas.

A2 é o que aconteceu de verdade: a `AplicarMenuPadraoAction` hardcodava `'grupo-tab-rh'`,
`'ref-cnaes'` e outras, sem uma linha de `use`. A varredura de views, database, config e
routes entrou em 2026-10 (#105), quando o dashboard e duas páginas de `/admin/dev` ainda
chamavam `\HT2ML\ExemploDemo\…` atrás de um `class_exists()` e o `RolePermissionSeeder`
concedia permissão de extensão.

Uma meta-checagem exige que a lista de chaves contenha `grupo-tab-rh`, `rh-funcionarios`,
`rh.funcionarios.listar` e `cnaes.listar`: se a derivação quebrar, o teste cai em vez de
passar sem ter procurado nada.

### Coerência do módulo (`CoerenciaDoModuloTest.php`)

A chave de um módulo aparece em cinco lugares que precisam concordar
([ADR-0021](architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)):

```
extra.ht2ml.chave == sufixo do diretório (packages/extensao-<chave>)
                  == ModuleRegistry::modulo('<chave>') no ServiceProvider
                  == namespace de view do loadViewsFrom()
                  == nome do arquivo config/<chave>.php
                  == prefixo das permissões
```

O teste reprova pacote sem `extra.ht2ml.tipo` (`core`, `modulo` ou `biblioteca`) e confere
as cinco igualdades para cada `modulo`. `extensao-fiscal-br` e `extensao-exemplo-demo`
estão numa lista de legado para o prefixo das permissões, com o motivo escrito. A
meta-checagem `toHaveCount(3)` precisa subir a cada módulo novo
([`guias/criar-extensao.md`](guias/criar-extensao.md) §12).

### Plataforma (`PlataformaTest.php`)

| Regra                                                     | Alvo                           |
| --------------------------------------------------------- | ------------------------------ |
| todo o código da plataforma tem `declare(strict_types=1)` | todo namespace de `packages/*` |
| o app do monorepo tem `declare(strict_types=1)`           | `App`                          |
| services não retornam respostas HTTP                      | `HT2ML\Core\Services`          |
| actions não retornam respostas HTTP                       | `HT2ML\Core\Actions`           |
| models usam `Auditavel` ou estão na whitelist consciente  | todo model de `packages/*`     |
| models de Referência não são tenant-scoped                | `HT2ML\Core\Models\Referencia` |

A whitelist de `Auditavel` (`->ignoring([...])`) tem, em 2026-10-25: `Activity` (o log em
si), `LoginHistory` (histórico append-only), `DocumentSequence` (contador técnico) e, na
branch `feat/extensao-ia`, `TraducaoEmCache` (cache apagável). Model novo sem auditoria
entra ali **com o motivo** no comentário.

A lista de namespaces vem de `namespacesDosPacotes()` (em `tests/Pest.php`), derivada dos
`composer.json`: extensão nova entra nas regras sozinha. `expect('HT2ML')` **não**
funciona — o Pest Arch resolve o alvo contra os prefixos PSR-4 (`HT2ML\Core`,
`HT2ML\Rh`…), e um prefixo que não casa devolve conjunto vazio e passa sem testar.

### As regras mordem (`tests/Unit/Arquitetura/RegrasDeArquiteturaTest.php`)

Testes sobre os testes: o `phpunit.xml` declara a suíte `Arch`, o `Pest.php` a coleta, o
CI e o `Makefile` a rodam, ela tem pelo menos cinco `arch()`, todo pacote de `packages/`
entra em `namespacesDosPacotes()`, e cada namespace alvo casa com um prefixo PSR-4 e tem
classes no classmap. Existe porque o `tests/Arch.php` original nunca rodou, por três
omissões independentes (nenhuma suíte o cobria, nenhum `->in()` o alcançava, o CI não o
chamava).

### Guardas do core em `tests/Feature/Core/`

Uma por descoberta automática que morre num pacote, e mais as do gerador:
`PoliciesDoCoreTest`, `ComandosDoCoreTest`, `ListenersDoCoreTest`,
`ComponentesLivewireDoCoreTest` (falham nos dois sentidos: registro que sumiu ou classe
que ninguém registra), `MarcadoresDoGeradorTest` (todo módulo tem os comentários-marcador
que o `make:recurso` procura; contagem fixa), `StubsDoGeradorTest`,
`InflexaoDoGeradorTest`, `MakeRegraTest`, `RenomeacaoDosComandosTest`, `ViewsDoPowerGridTest`
e `FiltrosDoPowerGridRenderizamTest`. Ver [`superficie-do-core.md`](superficie-do-core.md).

## Guardas no CI (`.github/workflows/ci.yml`)

| Job            | O que garante                                                                                                               |
| -------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `quality`      | Pint `--test`, PHPStan nível 6, ESLint, `composer audit` (informativo), **analisador de dependências** e Prettier `--check` |
| `core-sozinho` | **A4**: o core instala e funciona num Laravel limpo, sem o app nem as extensões                                             |
| `tests`        | `ht2ml:doutor` e `php artisan test --compact --testsuite=Unit,Feature,Extensoes,Arch` em SQLite                             |
| `browser`      | build do Vite e `./vendor/bin/pest --group=browser` com o Chromium do Playwright                                            |

### A4 — `core-sozinho`

O monorepo nunca exercita o caminho de um produto: lá o core convive com o app, as
extensões e um `vendor/` povoado, e uma dependência que o core usa sem declarar passa
despercebida. O job:

1. cria um `laravel/laravel` limpo em `/tmp/prova`;
2. instala **só** `ht2ml/core` (path repository para `packages/*`, `@dev`);
3. `migrate`, `access:sync` e `ht2ml:doutor`;
4. confere a superfície: mais de 50 rotas `admin.*`, `config('access.modules')` e
   `config('admin-menu')` não vazios;
5. faz o primeiro dia de um produto: `make:modulo sonda`, `composer require`,
   `make:recurso Leitura --modulo=sonda`, migra, sincroniza, `ht2ml:doutor`, e exige a rota
   `admin.sonda.leituras.index` e **6** permissões `sonda.*` na área `negocio`;
6. prova que `make:recurso` sem `--modulo` falha, ensina `make:modulo` e não escreve
   `app/Models/Avulso.php`.

### Analisador de dependências

O passo "Cada pacote declara o que usa" roda o `shipmonk/composer-dependency-analyser` em
cada pacote da lista do `for p in ...` do `ci.yml`, com a config da raiz
(`composer-dependency-analyser.php`). Ele responde "esta classe vem de um pacote que este
`composer.json` declara?", pergunta que não dá para escrever em Pest sem reimplementar o
Composer.

Como num monorepo com path repositories os pacotes não têm `vendor/` próprio, o CI cria um
symlink temporário. Para rodar à mão:

```bash
ln -sfn ../../vendor packages/extensao-rh/vendor
./vendor/bin/composer-dependency-analyser \
    --composer-json=packages/extensao-rh/composer.json \
    --config=composer-dependency-analyser.php
rm -f packages/extensao-rh/vendor
```

Ele cobre o que nenhum outro guard cobre: **extensão usando classe de outra extensão** sem
declarar. Os ignores da config são por pacote **e caminho** de propósito — um ignore global
apagaria essa checagem. Na primeira execução, pegou o `ezyang/htmlpurifier` que ninguém
declarava (chegava por dependência do Excel e é usado pelo `HtmlSanitizer`), o
`extensao-rh` usando Livewire e PowerGrid sem declarar e o `ext-intl` do `Money`.

A lista do `for` não é derivada: pacote novo precisa entrar nela, e no mapa da config
([`guias/criar-extensao.md`](guias/criar-extensao.md) §12).

### PHPStan

Nível 6 (`phpstan.neon`), sobre `packages/core/src/` e o `src/` de cada extensão listada em
`paths`. O Larastan infere colunas dos models a partir das migrations listadas em
`databaseMigrationsPath` — por isso as migrations de cada extensão também entram ali.
Também é lista manual.

## Checagens fora do CI

- **`python3 bin/checar-referencias-relativas.py .`** — acha referência relativa que não
  resolve depois de mover classe entre namespaces (o irmão citado sem `use` passa a apontar
  para o namespace novo). Sai com 1 se achar algo. Não roda no CI; rode depois de qualquer
  movimentação de classe.
- **`php artisan ht2ml:doutor`** — roda no CI, mas também é o diagnóstico local depois de
  gerar recurso ou instalar pacote. Ver [`comandos.md`](comandos.md#ht2mldoutor).
