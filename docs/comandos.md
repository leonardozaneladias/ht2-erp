# Comandos da plataforma

Referência dos comandos artisan que a plataforma entrega. Assinaturas e descrições
copiadas de `$signature` e `$description` de cada classe em
`packages/core/src/Console/Commands/` e `packages/extensao-*/src/Console/`
(conferido em 2026-10-25). Na dúvida, `php artisan help <comando>` mostra a versão
instalada.

Os comandos do core são registrados **explicitamente** no `CoreServiceProvider`
(`registrarComandos()`), e só em console. Dentro de um pacote não existe a
descoberta automática de `app/Console/Commands`; sem o registro eles somem do
`artisan` sem erro. `tests/Feature/Core/ComandosDoCoreTest.php` falha se algum sumir.

| Comando           | Pacote               | Para quê                                                       |
| ----------------- | -------------------- | -------------------------------------------------------------- |
| `make:modulo`     | `ht2ml/core`         | Cria a casca de um módulo (pacote em `packages/`)              |
| `make:recurso`    | `ht2ml/core`         | Gera o CRUD completo de um recurso dentro de um módulo         |
| `make:regra`      | `ht2ml/core`         | Gera uma regra de validação reutilizável e o teste dela        |
| `ht2ml:doutor`    | `ht2ml/core`         | Diagnostica as contribuições dos módulos                       |
| `access:sync`     | `ht2ml/core`         | Sincroniza o catálogo de permissões com a tabela `permissions` |
| `access:expirar`  | `ht2ml/core`         | Revoga concessões de acesso direto vencidas                    |
| `referencia:sync` | `ht2ml/core`         | Sincroniza os catálogos de referência a partir dos CSV         |
| `make:extensao`   | `ht2ml/core`         | Lápide: removido, aponta para `make:modulo`                    |
| `api:openapi`     | `ht2ml/extensao-api` | Gera ou verifica o arquivo OpenAPI da API                      |

## Geração

### `make:modulo`

```
make:modulo
    {chave : Chave do módulo em kebab-case (ex.: escola, fiscal-br)}
    {--force : Sobrescreve a casca se o pacote já existir}
    {--fields= : REMOVIDO em 2026-08-28 — pertence ao make:recurso}
    {--tenant : REMOVIDO em 2026-08-28 — pertence ao make:recurso}
```

_Cria um módulo como pacote em packages/ e registra o path repository._

Escreve `packages/extensao-<chave>/` com `composer.json` (`extra.ht2ml.tipo = modulo`,
`extra.ht2ml.chave = <chave>`), `src/<Studly>ServiceProvider.php`, `config/<chave>.php`,
`routes/admin.php`, `README.md`, `.gitignore` e as pastas vazias `database/migrations`,
`database/factories`, `resources/views` e `tests`. Acrescenta o path repository
`packages/*` ao `composer.json` raiz, se faltar. Depois:

```bash
composer require ht2ml/extensao-<chave>:@dev
```

Chave em PascalCase, ou `--fields`/`--tenant`, é a forma antiga (até 2026-08-28 o
comando era o gerador de CRUD): o comando recusa e mostra os dois comandos novos.

### `make:recurso`

```
make:recurso
    {nome : Nome do recurso no singular, PascalCase (ex.: Produto)}
    {--fields= : Lista "nome:tipo:modificador" separada por vírgula}
    {--tenant : Vincula o recurso à empresa ativa (trait BelongsToEmpresa)}
    {--modulo= : Chave do módulo que recebe o recurso, kebab-case (ex.: rh, fiscal-br)}
    {--menu= : Rótulo do item de menu (default: nome no plural)}
    {--menu-icon=tabler--folder : Ícone do item de menu (Tabler)}
    {--skip-menu : Não injeta o item no menu lateral (config/admin-menu.php)}
    {--sem-soft-delete : Desativa o soft-delete (por padrão registros são recuperáveis via deleted_at)}
    {--force : Sobrescreve arquivos existentes}
    {--module= : REMOVIDO em 2026-08-28 — use --modulo com a chave kebab-case}
```

_Gera o CRUD completo de um recurso (model, DTO, actions, Livewire, PowerGrid, views,
teste, rotas, permissões e item de menu)._

Sem `--modulo`, recusa antes de escrever, num produto que não tem `routes/admin.php`,
`config/access.php` e `config/admin-menu.php` próprios (o caso de todo produto que
instala o core). Sai com código de falha quando algum comentário-marcador falta e a tela
não fica ligada. Guia completo: [`criar-recurso.md`](criar-recurso.md).

### `make:regra`

```
make:regra
    {nome : Nome da regra em PascalCase (ex.: MatriculaValida)}
    {--modulo= : Chave do módulo que recebe a regra, kebab-case (ex.: escola)}
    {--mensagem= : Mensagem de falha (default: derivada do nome)}
    {--force : Sobrescreve arquivos existentes}
```

_Gera uma regra de validação reutilizável (ValidationRule) e o teste dela._

Com `--modulo=escola`: `packages/extensao-escola/src/Rules/<Nome>.php` e
`packages/extensao-escola/tests/Unit/Rules/<Nome>Test.php`. Sem: `app/Rules/<Nome>.php`
e `tests/Unit/Rules/<Nome>Test.php`.

### `make:extensao`

```
make:extensao {nome? : (removido)}
```

_REMOVIDO em 2026-08-28 — use make:modulo._ Existe só para falhar ensinando o nome novo.

`make:modulo`, `make:recurso` e `make:regra` usam a inflexão configurada em
`extensoes.idioma_inflexao` (default `null`, inglês) durante a geração. Ver
[`criar-recurso.md`](criar-recurso.md#nomes-em-português-idioma_inflexao).

## Diagnóstico

### `ht2ml:doutor`

```
ht2ml:doutor {--json : Emite o relatório em JSON}
```

_Verifica se as contribuições dos módulos fecham: áreas, seções, grupos, permissões,
rotas e ícones._

Verificações, na ordem do `handle()`:

1. contribuições que o `ModuleRegistry` descartou ao aplicar;
2. toda área de permissão existe em `access.areas`;
3. todo item de menu aponta para um grupo existente;
4. toda permissão exigida por um item de menu existe no catálogo;
5. toda rota de item de menu existe;
6. todo ícone de menu existe no conjunto disponível;
7. todo `EscopoDeRota` com rotas registradas tem o arquivo hospedeiro no core
   (`packages/core/routes/{admin,publico,webhook,api}.php`);
8. todo pacote instalado com `extra.ht2ml.tipo = modulo` declara `extra.ht2ml.chave`, e
   essa chave foi declarada com `ModuleRegistry::modulo()` no boot.

Sai com **código 1** se houver qualquer problema. Rode depois de gerar um recurso, depois
de instalar ou atualizar um pacote e no pós-deploy. O CI roda no job `tests` e no
`core-sozinho`; `make doutor` roda no container DDEV.

## Operação e deploy

### `access:sync`

```
access:sync
    {--prune : Remove permissões órfãs (ausentes do catálogo) que não estão em uso}
    {--dry-run : Apenas exibe o que seria feito, sem gravar}
```

_Sincroniza o catálogo de permissões (config/access.php) com a tabela permissions._

O catálogo é o `config('access.modules')` já com as contribuições dos módulos aplicadas.
Cria permissões novas e atualiza rótulo, descrição e área das existentes (guard `admin`).
Idempotente. Passo de deploy e de pós-instalação de pacote; sem ele, a permissão de um
recurso novo não existe no banco e só o super-admin enxerga a tela.

### `access:expirar`

```
access:expirar
```

_Marca como revogadas as concessões de acesso direto que já venceram._

Agendado de hora em hora em `routes/console.php` do app
(`Schedule::command('access:expirar')->hourly()`). Exige o cron do `schedule:run`.

### `referencia:sync`

```
referencia:sync
    {conjunto?* : Limita a conjuntos específicos (ex.: estados); vazio = todos}
    {--dry-run : Executa sem gravar (rollback ao final)}
```

_Sincroniza os dados de referência (CSV → catálogos globais), idempotente._

Conjuntos do core: `paises`, `estados`, `municipios`, `moedas`, `bancos`, `cargos`,
`tipos_logradouro`. Extensões acrescentam os seus com
`ModuleRegistry::catalogoDeReferencia($slug, $seeder)` — a `extensao-fiscal-br` declara
`cnaes`, `cfops` e `ncms`. Linhas de origem `manual` (criadas pelo cliente) não são
tocadas. Passo de deploy quando um CSV muda.

## Extensões

### `api:openapi` (`ht2ml/extensao-api`)

```
api:openapi {--verificar : Só compara, sem gravar; sai com erro se divergir}
```

_Gera ou verifica o arquivo OpenAPI da API._

Sem opção, grava `config('api.openapi.arquivo')` (default `docs/openapi.json`) usando o
`scramble:export`. Com `--verificar`, compara sem gravar e sai com erro se o arquivo não
descreve mais a API — é o portão de CI do produto. A comparação ignora `servers`, que o
Scramble deriva do `APP_URL`. Registrado pelo `ApiServiceProvider` só em console.

## Atalhos do Makefile

O `Makefile` da base embrulha alguns destes no DDEV: `make doutor`
(`ddev exec php artisan ht2ml:doutor`), `make migrate`, `make fresh`
(`migrate:fresh --seed`) e `make test`
(`ddev artisan test --parallel --testsuite=Unit,Feature,Extensoes,Arch`). Release de
pacote: `make release-modulo pacote=<dir> versao=<vX.Y.Z>` (ver
[`devops/release.md`](devops/release.md)).
