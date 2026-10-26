# ht2ml/core

O núcleo da plataforma HT2ML. Um produto instala este pacote e ganha o backoffice
inteiro: auth com 2FA, multiempresa, perfis e permissões, auditoria, configurações,
menu, design system, catálogos de referência e os geradores. Nada disso vive no
produto ([ADR-0017](../../docs/architecture/adrs/ADR-0017-produto-novo-via-skeleton.md),
[ADR-0019](../../docs/architecture/adrs/ADR-0019-plataforma-abstrata-sem-produto.md)).

O core não conhece extensão nenhuma, nem por classe nem por literal de string
([ADR-0022](../../docs/architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)).
Extensões entram pelos canais abaixo.

## Instalar

O caminho normal é o skeleton, que já traz este pacote no `composer.json`
([`docs/guias/produto-via-skeleton.md`](../../docs/guias/produto-via-skeleton.md)).
O pacote é privado e vem por VCS do repositório `leonardozaneladias/ht2ml-core`.

```bash
composer require ht2ml/core
php artisan migrate
php artisan access:sync
php artisan ht2ml:doutor
```

O CI deste repositório prova esse caminho a cada push (job "Core sozinho num Laravel
limpo"): Laravel limpo, só o `ht2ml/core`, migrate, `access:sync`, `ht2ml:doutor` e a
sequência `make:modulo` → `composer require` → `make:recurso` → tela alcançável.

## Canais para extensões

| Símbolo | Papel |
| --- | --- |
| `Support\Modules\ModuleRegistry` | **O canal.** Rotas, seeders, permissões, itens de menu, áreas de acesso, seções e grupos de menu, catálogos de referência |
| `Support\Modules\ModuloBuilder` | `ModuleRegistry::modulo('{chave}')`: declara um módulo; `->deConfig('{chave}')` lê área, seção, grupos e recursos de `config/{chave}.php` e deriva permissões e itens de menu |
| `Support\Modules\RecursoBuilder` | Um recurso do módulo: dele saem seis permissões, o item de menu, o nome da rota e o padrão de `active` |
| `Support\Modules\EscopoDeRota` | Onde a rota do módulo entra: `Admin` (padrão), `Publico`, `Webhook` ou `Api` |
| `Support\Access\AreaDeAcesso` | Área do catálogo de permissões, conjunto aberto (`config('access.areas')`) |
| `Enums\ModuloAcesso` | A semente: as áreas que o próprio core declara |
| `Contracts\Referencia\*` | Contratos que o core declara para o que uma extensão pode fornecer |

## O que um recurso de extensão costuma usar

| Símbolo | Papel |
| --- | --- |
| `Livewire\Grid\{RecursoTable,RecursoIndex,Campo,RecursoMultiEmpresa}` | Base declarativa das listagens: uma lista de `Campo` vira colunas, filtros, exportação e validação |
| `Livewire\Concerns\{ComAcoesCrud,ComFicha,ComLixeira,EmiteNotificacoes}` | Comportamentos de tela (ações, ficha "Ver", lixeira, toasts) |
| `Models\Concerns\BelongsToEmpresa` + `Support\Tenancy\TenantContext` | Isolamento por empresa ativa |
| `Models\{AdminUser,Empresa,Filial}` | Os models que um recurso referencia |
| `Casts\MoneyCast` | Dinheiro em centavos ([ADR-0014](../../docs/architecture/adrs/ADR-0014-money-integer-centavos.md)) |
| `Models\Contracts\UsaSoftDeletes` | Contrato de soft delete |
| `Support\Referencia\CsvReferenceSeeder` | Base dos seeders de catálogo mantidos por CSV |
| `Enums\Referencia\OrigemRegistro`, `Models\Concerns\TemOrigem`, `Policies\Referencia\Concerns\ProtegeRegistroSincronizado` | Separam linha do sync (somente-leitura) de linha do cliente |

A lista medida, com contagens, está em
[`docs/superficie-do-core.md`](../../docs/superficie-do-core.md).

## O que vem junto

- Rotas: `routes/{admin,publico,webhook,api}.php`, os hospedeiros de cada escopo.
- Config: `config/{access,admin-menu,branding,extensoes,settings}.php`.
- Views e componentes Blade (`x-admin.*`, `x-shared.*`) em `resources/views/`.
- Stubs dos geradores em `stubs/{modulo,extensao}/`. Um produto sobrescreve um stub
  criando o arquivo de mesmo nome em `stubs/` da raiz dele.
- Middleware `webhook.assinatura:{chave_de_config}[,{header}]` para webhooks assinados
  com HMAC-SHA256.

## Comandos

`ht2ml:doutor`, `make:modulo`, `make:recurso`, `make:regra`, `access:sync`,
`access:expirar` e `referencia:sync`. `make:extensao` só existe para dizer que foi
renomeado. Ver [`docs/comandos.md`](../../docs/comandos.md).
