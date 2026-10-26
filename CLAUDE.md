# HT2ML Platform — Inspinia + Livewire

> Este arquivo é lido automaticamente pelo Claude Code ao abrir o projeto.
> Contém todo o contexto necessário para entender, desenvolver e manter o sistema.

---

## 1. O Que É Este Projeto

**Plataforma de backoffice administrativo** em Laravel + Blade + Inspinia + Livewire. Ela é abstrata: nenhum produto vive dentro dela ([ADR-0019](docs/architecture/adrs/ADR-0019-plataforma-abstrata-sem-produto.md)). O código da plataforma é o pacote `ht2ml/core`, em `packages/core/`; o que é de um domínio de negócio vai para extensões em `packages/extensao-*`. A raiz deste repositório é o app de desenvolvimento e vitrine, que instala esses pacotes por path repository. Vocabulário em [`CONTEXT-MAP.md`](CONTEXT-MAP.md).

**Um único ambiente admin** — painel backoffice desktop-first (1366×768 mín).
Template: **Inspinia** (Tailwind CSS 4) + **Livewire 4** para componentes reativos.

Não há portal SPA, não há React, não há multi-guard de portal.
Toda a UI é server-side: Blade + Livewire + Alpine.js.

---

## 2. Stack Tecnológica

| Camada                | Tecnologia                             | Versão |
| --------------------- | -------------------------------------- | ------ |
| Framework             | Laravel                                | ^13    |
| PHP                   | PHP                                    | 8.4    |
| Banco de Dados        | PostgreSQL                             | 16     |
| Cache / Sessão / Fila | Redis                                  | latest |
| Frontend Admin        | Livewire 4 + Inspinia + Tailwind CSS 4 | ^4.2   |
| Auth                  | Guard `admin` (AdminUser)              | —      |
| ACL                   | spatie/laravel-permission              | ^8.0   |
| Auditoria             | spatie/laravel-activitylog             | ^5.0   |
| Filas                 | Laravel Horizon                        | ^5.0   |
| Monitoramento         | Laravel Pulse                          | ^1.0   |
| Grid / CRUD           | power-components/livewire-powergrid    | ^6.10  |
| Build                 | Vite                                   | ^8.2   |
| Testes                | Pest                                   | ^4.4   |
| Ambiente              | DDEV + OrbStack (macOS)                | —      |

---

## 3. Arquitetura — Regras Fundamentais

### 3.1 Autenticação Admin (guard único)

```
Guard:      admin (AdminUser)
Tabela:     admin_users
Rotas:      packages/core/routes/admin.php
Prefixo:    /admin/*
Layout:     packages/core/resources/views/components/admin/layout.blade.php
Middleware: admin.auth (AdminAuthenticate)
Controllers: HT2ML\Core\Http\Controllers\Admin
Livewire:   HT2ML\Core\Livewire\Admin
CSS/JS:     resources/css/admin.css / resources/js/admin.js
```

Rotas web: `routes/web.php` → redireciona `/` para `/admin/dashboard`.
Rotas admin: `packages/core/routes/admin.php` → auth + dashboard + telas do core + dev/components.

Um módulo nunca edita as rotas do core. Ele declara as suas no ServiceProvider, pelo
`ModuleRegistry::modulo('{chave}')` (um `ModuloBuilder`), e escolhe o canal com o enum
`EscopoDeRota`:

| Escopo    | Atalho no builder    | Onde a rota entra                                                      |
| --------- | -------------------- | ---------------------------------------------------------------------- |
| `Admin`   | `->rotas()` (padrão) | grupo autenticado `/admin`, name `admin.`, todo o middleware do painel |
| `Publico` | `->rotasPublicas()`  | stack `web`, sem login e sem prefixo                                   |
| `Webhook` | `->rotasDeWebhook()` | sem sessão e sem CSRF, sob `/webhooks`, com `throttle:webhooks`        |
| `Api`     | `->rotasDeApi()`     | grupo `api`, sob `/api`, name `api.`; auth e versão ficam com o módulo |

Os hospedeiros são `packages/core/routes/{admin,publico,webhook,api}.php`. Para webhook,
o core oferece o middleware `webhook.assinatura:{chave_de_config}[,{header}]` (HMAC-SHA256
do corpo bruto). Sanctum e o resto da camada de API ficam na extensão `ht2ml/extensao-api`
([ADR-0023](docs/architecture/adrs/ADR-0023-api-rest-como-extensao.md)).

### 3.2 Vite — Entry Points

```js
// vite.config.js
input: ['resources/css/admin.css', 'resources/js/admin.js'];
```

### 3.3 Multi-tenancy lógico (empresa/filial)

Uma instalação por cliente, porém **multi-tenant lógico**: várias **empresas** e
**filiais** na mesma instância, com isolamento de dados, papéis por empresa e branding
por empresa ativa. Regras essenciais:

- Todo registro de negócio tem `empresa_id`; use o trait `HT2ML\Core\Models\Concerns\BelongsToEmpresa`
  (global scope por empresa ativa + auto-preenche no `creating`). `filial_id` é opcional.
- Tenant ativo: `HT2ML\Core\Support\Tenancy\TenantContext` (sessão), hidratado pelo middleware
  `DefinirContextoTenant`. `unique` sempre por empresa. Escape consciente: `withoutGlobalScope('empresa')`.
- RBAC de **dois níveis**: papéis globais (spatie, todas as empresas) + papéis por empresa
  (`admin_user_empresa_role`, geridos no hub no escopo da empresa ativa). super-admin é sempre global.

**Detalhes e como criar módulos tenant-scoped:** [`docs/multi-empresa.md`](docs/multi-empresa.md).

---

## 4. Idioma e Comunicação

**Todo o projeto é em Português (PT-BR).** Isso inclui:

- Commits: `feat(admin): implementar listagem de clientes`
- Mensagens de flash/toast: `'Registro salvo com sucesso.'`
- Variáveis de negócio no domínio: nomes em PT-BR
- Enums labels: `'Ativo'`, `'Inativo'` (não `'Active'`)
- Validação: mensagens em pt_BR

**Exceções (inglês):** classes/métodos PHP, tabelas/colunas BD, nomes de rotas, componentes Blade, código em geral, termos técnicos.

---

## 5. Padrões Obrigatórios de Código

- **5.1 Controller magro** — máximo 5-7 linhas por método; delegar para Service/Action
- **5.2 FormRequest obrigatório** — toda validação de input; nunca validar no Controller
- **5.3 Dinheiro em centavos** — colunas `INTEGER` no banco; nunca `float`
- **5.4 Enums backed** — `StatusPedido: string { case PENDENTE = 'pendente'; ... }`; nunca strings soltas
- **5.5 DTOs readonly** — `readonly class FooDTO { ... }`; nunca arrays genéricos entre camadas
- **5.6 Services API-Ready** — nunca recebem `Request`; retornam DTO; quem formata é o Controller
- **5.7 `declare(strict_types=1)`** — obrigatório em todo arquivo PHP
- **5.8 Type hints e return types** — em todos os métodos, propriedades e parâmetros

---

## 6. Quando Usar Cada Padrão

`FormRequest` → validação · `Controller` → thin (chama Action/Service) · `Service` → regra reutilizável · `Action` (execute()) → operação atômica · `Job` → assíncrono via Horizon · `Event+Listener` → reação a fatos · `Observer` → auditoria automática · `Middleware` → antes/depois de toda request · `Policy` → autorização por recurso · `DTO` (readonly) → transporte entre camadas · `Enum` (backed) → valores finitos.

---

## 7. Naming Conventions

| Tipo                          | Padrão                          | Exemplo                                       |
| ----------------------------- | ------------------------------- | --------------------------------------------- |
| Model                         | PascalCase singular             | `Cliente`                                     |
| Controller/Service/Action/Job | PascalCase + sufixo             | `ClienteController`, `CreateClienteAction`    |
| FormRequest                   | Store\|Update + Request         | `StoreClienteRequest`                         |
| Enum / DTO                    | PascalCase + sufixo             | `StatusCliente`, `ClienteDTO`                 |
| Tabela BD / Coluna            | snake_case                      | `clientes`, `nome_completo`                   |
| Rota name                     | dot.notation                    | `admin.clientes.store`                        |
| Rota URI / Blade / Component  | kebab-case                      | `/admin/clientes`, `<x-admin.kpi-card>`       |
| Commit                        | `tipo(escopo): descrição pt-BR` | `feat(admin): adicionar listagem de clientes` |

---

## 8. Formatadores e Qualidade

| Ferramenta          | O Que Faz                               | Comando                           |
| ------------------- | --------------------------------------- | --------------------------------- |
| Laravel Pint        | Formata PHP (PSR-12 + Laravel)          | `./vendor/bin/pint`               |
| Prettier            | Formata Blade, JS, CSS, JSON, MD        | `npx prettier --write resources/` |
| ESLint              | Lint de JS                              | `npm run lint:js`                 |
| PHPStan/Larastan    | Análise estática PHP (level 6)          | `./vendor/bin/phpstan analyse`    |
| Pest                | Testes (Unit, Feature, Extensoes, Arch) | `php artisan test`                |
| Pest Browser        | Testes E2E (Playwright)                 | `make test-e2e`                   |
| Husky + lint-staged | Roda tudo no git commit                 | Automático                        |

**Verificação completa:** `npm run quality`

### Testes E2E (browser)

Testes de browser vivem em `tests/Browser/` (grupo `browser`) e rodam com
**Playwright** via `pestphp/pest-plugin-browser`. Rodam no **host** (não no
container DDEV, que não tem Chromium); `make test`/CI excluem o grupo. Cobrem
smoke das telas (sem erros de JS) e regressões de UI (z-index dos dropdowns do
PowerGrid, filtros multi-select).

```bash
make test-e2e            # build do Vite + pest --group=browser (host)
npx playwright install chromium   # 1x, baixa o browser
```

---

## 9. Componentes Blade — REGRA OBRIGATÓRIA

### Onde ficam

```
packages/core/resources/views/components/
├── admin/      ← Componentes exclusivos do admin (Inspinia)
└── shared/     ← Componentes compartilhados
```

Os componentes vêm do core. Um produto pode sobrescrever um deles criando o arquivo
de mesmo nome em `resources/views/components/` da raiz, mas não deve editar o pacote.

### Fluxo antes de escrever qualquer HTML admin

1. Abrir `docs/template/INSPINIA/CATALOGO-COMPONENTES.md` e verificar status
2. 🟢 use direto · 🟡 pare, registre decisão · 🔴 componentize primeiro
3. Se não existe: doc em `docs/template/INSPINIA/` → atualizar catálogo → criar `.blade.php` → consumir

### Componentes disponíveis

**Fonte de verdade:** [`docs/template/INSPINIA/CATALOGO-COMPONENTES.md`](docs/template/INSPINIA/CATALOGO-COMPONENTES.md). Sempre consulte o catálogo antes de escrever HTML — a lista evolui, e manter cópia inline aqui desincroniza rápido.

Exemplos de uso comum:

- Layout / chrome: `x-admin.layout`, `x-admin.auth-layout`, `x-admin.sidebar`, `x-admin.topbar`, `x-admin.page-header`, `x-admin.drawer`
- Tabelas / dashboards: `x-admin.data-table`, `x-admin.kpi-card`, `x-admin.chart-*`
- Formulários: `x-shared.input`, `x-shared.select-search`, `x-shared.toggle`, `x-shared.money-input`, `x-shared.cpf-input`, `x-shared.password-input`
- Feedback: `x-shared.alert`, `x-shared.toast-container`, `x-shared.modal`, `x-shared.empty-state`

### Showcase interativo (só em local)

Acesse `/admin/dev/components` para ver todos os componentes com exemplos ao vivo.

### Outras regras

- Namespace: `x-admin.*` (admin), `x-shared.*` (ambos)
- Alpine.js só para interações visuais locais (toggle, collapse, tooltip)
- Livewire para qualquer interação que envolva dados do servidor
- Nunca CSS customizado — usar Tailwind classes

---

## 10. Livewire — Regras

```
packages/core/src/Livewire/Admin/              ← Componentes Livewire do admin (core)
packages/core/resources/views/livewire/admin/  ← Views dos componentes
packages/extensao-*/src/Livewire/              ← Componentes de cada módulo
```

- Componente de exemplo: `packages/extensao-exemplo-demo/src/Livewire/ExemploCounter.php`
- Use como tag: `<livewire:exemplo-counter />` (a extensão registra o namespace com `Livewire::addLocation()`)
- O layout já inclui `@livewireScripts` quando `$withLivewire` é true (passado via `<x-admin.layout :withLivewire="true">`)
- Ou inclua `@livewireScripts` manualmente na view se necessário

---

## 11. Banco de Dados

- **PostgreSQL 16**
- Valores monetários: colunas `INTEGER` (centavos)
- Índices em FKs, campos de filtro, status, datas
- Status operacional via campo `ativo` (liga/desliga; o registro segue visível)
- **Lixeira** via `deleted_at` (`SoftDeletes`), independente de `ativo`: excluir manda para a lixeira (some da listagem, restaurável). Trait genérico `HT2ML\Core\Livewire\Concerns\ComLixeira`; 3 níveis de permissão por módulo (`deletar`→lixeira, `restaurar`, `excluir_permanente`→force-delete). Models implementam `HT2ML\Core\Models\Contracts\UsaSoftDeletes`. Ver [`docs/lixeira.md`](docs/lixeira.md).
- `activity_log`: append-only (spatie/laravel-activitylog)

### Migrations de infra disponíveis (não alterar)

Na raiz (`database/migrations/`), só as do app Laravel:

- `create_users_table` — tabela users padrão Laravel
- `create_cache_table` — tabela do driver de cache `database` (o `.env.example` usa Redis)
- `create_jobs_table` — filas
- `create_pulse_tables` — Pulse monitoring

O resto vem do core, em `packages/core/database/migrations/` (42 arquivos, contados em 2026-10-25), entre elas:

- `create_permission_tables` — Spatie Permission
- `create_activity_log_table` — Spatie Activitylog
- `create_admin_users_table` — auth admin
- `create_settings_table` — Spatie Settings (configurações do sistema)

---

## 12. Pacotes Disponíveis (não remover sem motivo)

**Composer:**

- `livewire/livewire` — UI reativa server-side
- `spatie/laravel-permission` — ACL (roles + permissions)
- `spatie/laravel-activitylog` — auditoria automática
- `spatie/laravel-settings` — configurações tipadas do sistema (tela de Configurações)
- `laravel/horizon` — dashboard de filas
- `laravel/pulse` — monitoramento de performance
- `power-components/livewire-powergrid` — tabelas das listagens (base `RecursoTable`)
- `barryvdh/laravel-dompdf` — PDFs
- `maatwebsite/excel` + `openspout/openspout` — exports CSV/Excel
- `spatie/laravel-backup` — backups
- `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code` — 2FA (TOTP e QR code)
- `guzzlehttp/guzzle` — cliente HTTP

**NPM (plugins Inspinia):**
ApexCharts · Flatpickr · Choices.js · Inputmask · SortableJS · Dropzone · SweetAlert2 ·
Quill · Cropper.js · SimpleBar · Preline (fonte: `package.json`)

---

## 13. Seeders e Usuários de Dev

```bash
php artisan migrate:fresh --seed
```

Cria:

- `admin@example.com` / `password` — role `super-admin`
- `gestor@example.com` / `password` — role `gestor`

O seed também marca o sistema como **instalado** (`GeneralSettings::instalado = true`), pulando o Setup Wizard. Em uma instalação limpa (`migrate` sem `--seed`), `instalado` é `false` e o primeiro acesso ao painel é redirecionado para `/admin/setup`.

Veja [`docs/configuracoes-sistema.md`](docs/configuracoes-sistema.md) para a arquitetura de configurações e o branding dinâmico. O enum de grupos e a tela de Configurações vivem no core; hoje um módulo não tem canal para acrescentar um grupo ou aba.

---

## 14. Filas e Jobs (Horizon)

```
Fila default  → Jobs gerais
Fila emails   → E-mails transacionais
Fila exports  → Relatórios CSV/Excel
Fila pdf      → PDFs
Fila gateway  → Integrações com gateways (supervisor de alta prioridade)
Fila webhooks → Processamento de webhooks recebidos (supervisor de alta prioridade)
```

Supervisores em `config/horizon.php`.

Dashboard: `/horizon`

---

## 15. Ambiente de Desenvolvimento

Ambiente oficial: **DDEV** (config em `.ddev/`), provider **OrbStack** no macOS.
Pré-requisitos: `brew install orbstack && brew install ddev/ddev/ddev`.

```bash
ddev start       # Subir o ambiente (containers + hooks)
make setup       # 1x: key, migrate --seed, assets Horizon/Pulse, build
make bash        # Shell no container web (ddev ssh)
make fresh       # migrate:fresh --seed
make dev         # Vite dev server (HMR)
make test        # Unit, Feature, Extensoes e Arch, em paralelo
make test-e2e    # Testes de browser (no host)
make lint        # Pint + Prettier
make quality     # Pint, ESLint, Prettier, PHPStan e testes (só confere)
make doutor      # ht2ml:doutor — diagnóstico das contribuições dos módulos
```

A lista completa de alvos está no `Makefile` e no [README](README.md).

URLs locais: App `https://ht2ml-platform.ddev.site`, Horizon `/horizon`, Pulse `/pulse`, Mailpit `ddev mailpit`.

---

## 16. Produto Novo e Módulos de Negócio

Um produto novo **não** é cópia deste repositório: ele nasce do skeleton por Composer
([ADR-0017](docs/architecture/adrs/ADR-0017-produto-novo-via-skeleton.md)). O `bin/init-project.sh`
está aposentado. Skeleton e pacotes `ht2ml/*` são privados: o `create-project` precisa do
`--repository` e de credencial com leitura nos repositórios `ht2ml-*`.

```bash
composer create-project ht2ml/skeleton meu-produto \
  --repository='{"type":"vcs","url":"git@github.com:leonardozaneladias/ht2ml-skeleton.git","no-api":true}'
cd meu-produto
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

Detalhes, extensões opcionais e atualização (`composer update ht2ml/core` → `migrate` →
`access:sync` → `ht2ml:doutor`): [`docs/guias/produto-via-skeleton.md`](docs/guias/produto-via-skeleton.md).
Instância de cliente (mesmo produto, outro cliente) é outro caminho: clone + re-origin
([ADR-0016](docs/architecture/adrs/ADR-0016-instancias-por-cliente.md)).

### Adicionando um módulo de negócio

**Use o gerador.** Todo recurso vive dentro de um módulo; sem `--modulo`, o `make:recurso`
recusa antes de escrever qualquer arquivo. Ele cria a stack CRUD inteira já no padrão
(migration, factory, model, enum de status, DTO, FormRequests+Rules, actions, service,
policy, Livewire Index/Form/Table, views, teste), insere a rota no `routes/admin.php` do
pacote e o recurso em `recursos` de `config/{modulo}.php`. Permissões, item de menu, nome
de rota e padrão de `active` são **derivados** dessa entrada pelo `ModuloBuilder`
(`{modulo}.{recurso}.listar` etc.), não escritos à mão:

```bash
php artisan make:modulo escola                    # uma vez por área de negócio
composer require ht2ml/extensao-escola:@dev
php artisan make:recurso Cliente --modulo=escola \
  --fields="nome:string, cnpj:cnpj, email:email:nullable, status:enum(ativo|inativo)" \
  --tenant
php artisan migrate && php artisan access:sync
php artisan ht2ml:doutor                          # área, seção, permissões, rotas e ícones fecham?
```

O Pint já roda nos arquivos gerados. Depois, atribua as permissões
(`/admin/acesso`); o item de menu já nasce com o recurso. Para uma regra de validação
ao lado do domínio, `php artisan make:regra MatriculaValida --modulo=escola`.
Guia completo (tipos de campo, flags, customização dos stubs):
[`docs/criar-recurso.md`](docs/criar-recurso.md). O recurso **Exemplo** (em
`packages/extensao-exemplo-demo/src/Livewire/Exemplos/`) é a referência viva. Os stubs ficam em
`packages/core/stubs/modulo/`; um produto pode sobrescrever um stub criando o arquivo de mesmo
nome em `stubs/modulo/` da raiz.

O módulo é distribuído como pacote Composer (`ht2ml/extensao-{chave}`, em
`packages/extensao-{chave}/`), sem editar o core. A chave vai em kebab-case — é ela que
vira prefixo de permissão, seção de menu, namespace de view e prefixo de rota (ver
[`ADR-0021`](docs/architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)). O core
nunca conhece uma extensão, nem por classe nem por literal de string
([`ADR-0022`](docs/architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)). Ver
[`ADR-0015`](docs/architecture/adrs/ADR-0015-modulos-pacotes-composer.md), o guia
[`criar uma extensão`](docs/guias/criar-extensao.md), a lista de [`comandos`](docs/comandos.md)
e o guia de [`distribuição e manutenção`](docs/distribuicao-manutencao.md).

---

## 17. Antes de Cada Tarefa

1. Verificar se o componente Blade já existe no catálogo (`docs/template/INSPINIA/CATALOGO-COMPONENTES.md`)
2. Verificar se o Service/Action já existe
3. Criar o FormRequest ANTES do Controller
4. Criar testes para Services críticos

### Estratégia de execução

Implementar em batches pequenos e controlados. Não componentizar tudo de uma vez. Pare e abra nova sessão de planejamento quando houver:

- bloqueio transversal (a mudança força tocar em N módulos não relacionados);
- conflito de API (uma assinatura proposta colide com convenção já existente);
- necessidade de rever catálogo ou convenções (decisão estrutural não prevista).

---

## 18. Depois de Cada Tarefa

1. Rodar `./vendor/bin/pint` (PHP format)
2. Rodar `npx prettier --write` nos arquivos Blade/JS/CSS alterados
3. Rodar `./vendor/bin/phpstan analyse` (corrigir warnings)
4. Rodar `php artisan test` (testes passando) e, se mexeu em módulo, `php artisan ht2ml:doutor`
5. Commit seguindo Conventional Commits: `tipo(escopo): descrição`

---

## 19. O Que NÃO Fazer (Anti-Patterns)

```
❌ Lógica de negócio no Controller (usar Service/Action)
❌ Validação no Controller (usar FormRequest)
❌ Float para dinheiro (usar int centavos)
❌ Strings soltas para status (usar Enum)
❌ Arrays para transporte de dados (usar DTO)
❌ Service recebendo Request como parâmetro
❌ Service retornando redirect/view/json
❌ Lazy loading sem eager load (causa N+1)
❌ Cache::forever() (sempre com TTL)
❌ Mail::send() síncrono (usar Mail::queue())
❌ throw new \Exception() genérica (usar exceção customizada)
❌ Copiar página inteira do Inspinia (decompor em componentes)
❌ CSS customizado (usar Tailwind classes)
❌ Deletar registros de activity_log (append-only)
❌ Commit sem rodar Pint + Prettier
❌ Misturar guard admin com outros guards
❌ Dados sensíveis em logs (senhas, tokens)
❌ <select> HTML direto nas views (usar x-shared.select-search)
❌ x-shared.select nativo (usar x-shared.select-search para single ou :multiple=true para multi)
```

---

## 20. Agent skills

Configuração que os skills de engenharia leem antes de agir.

### Issue tracker

Issues e specs vivem no **GitHub Issues** de `leonardozaneladias/ht2-erp`, operado via `gh` CLI. Ver [`docs/agents/issue-tracker.md`](docs/agents/issue-tracker.md).

### Domain docs

Layout **multi-context**: `CONTEXT-MAP.md` na raiz aponta para um `CONTEXT.md` por contexto (o glossário do core está em `app/CONTEXT.md`, embora o código do core viva em `packages/core/`; extensões em `packages/extensao-*/`); ADRs do sistema em `docs/architecture/adrs/`. Ver [`docs/agents/domain.md`](docs/agents/domain.md).
