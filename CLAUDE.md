# Laravel Admin Boilerplate — Inspinia + Livewire

> Este arquivo é lido automaticamente pelo Claude Code ao abrir o projeto.
> Contém todo o contexto necessário para entender, desenvolver e manter o sistema.

---

## 1. O Que É Este Projeto

**Starter kit** para sistemas administrativos com Laravel + Blade + Inspinia + Livewire.

**Um único ambiente admin** — painel backoffice desktop-first (1366×768 mín).
Template: **Inspinia** (Tailwind CSS 4) + **Livewire 4** para componentes reativos.

Não há portal SPA, não há React, não há multi-guard de portal.
Toda a UI é server-side: Blade + Livewire + Alpine.js.

---

## 2. Stack Tecnológica

| Camada                | Tecnologia                             | Versão |
| --------------------- | -------------------------------------- | ------ |
| Framework             | Laravel                                | 13.x   |
| PHP                   | PHP                                    | 8.4    |
| Banco de Dados        | PostgreSQL                             | 16     |
| Cache / Sessão / Fila | Redis                                  | latest |
| Frontend Admin        | Livewire 4 + Inspinia + Tailwind CSS 4 | —      |
| Auth                  | Guard `admin` (AdminUser)              | —      |
| ACL                   | spatie/laravel-permission              | ^7.0   |
| Auditoria             | spatie/laravel-activitylog             | ^5.0   |
| Filas                 | Laravel Horizon                        | ^5.0   |
| Monitoramento         | Laravel Pulse                          | ^1.0   |
| Build                 | Vite                                   | latest |
| Testes                | Pest                                   | ^4.0   |
| Ambiente              | DDEV + OrbStack (macOS)                | —      |

---

## 3. Arquitetura — Regras Fundamentais

### 3.1 Autenticação Admin (guard único)

```
Guard:      admin (AdminUser)
Tabela:     admin_users
Rotas:      routes/admin.php
Prefixo:    /admin/*
Layout:     resources/views/components/admin/layout.blade.php
Middleware: admin.auth (AdminAuthenticate)
Controllers: App\Http\Controllers\Admin
Livewire:   App\Livewire\Admin
CSS/JS:     resources/css/admin.css / resources/js/admin.js
```

Rotas web: `routes/web.php` → redireciona `/` para `/admin/dashboard`.
Rotas admin: `routes/admin.php` → auth + dashboard + módulos + dev/components.

### 3.2 Vite — Entry Points

```js
// vite.config.js
input: ['resources/css/admin.css', 'resources/js/admin.js'];
```

### 3.3 Multi-tenancy lógico (empresa/filial)

Uma instalação por cliente, porém **multi-tenant lógico**: várias **empresas** e
**filiais** na mesma instância, com isolamento de dados, papéis por empresa e branding
por empresa ativa. Regras essenciais:

- Todo registro de negócio tem `empresa_id`; use o trait `App\Models\Concerns\BelongsToEmpresa`
  (global scope por empresa ativa + auto-preenche no `creating`). `filial_id` é opcional.
- Tenant ativo: `App\Support\Tenancy\TenantContext` (sessão), hidratado pelo middleware
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

| Ferramenta          | O Que Faz                        | Comando                           |
| ------------------- | -------------------------------- | --------------------------------- |
| Laravel Pint        | Formata PHP (PSR-12 + Laravel)   | `./vendor/bin/pint`               |
| Prettier            | Formata Blade, JS, CSS, JSON, MD | `npx prettier --write resources/` |
| PHPStan/Larastan    | Análise estática PHP (level 6)   | `./vendor/bin/phpstan analyse`    |
| Pest                | Testes (unit/feature)            | `php artisan test`                |
| Pest Browser        | Testes E2E (Playwright)          | `make test-e2e`                   |
| Husky + lint-staged | Roda tudo no git commit          | Automático                        |

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
resources/views/components/
├── admin/      ← Componentes exclusivos do admin (Inspinia)
└── shared/     ← Componentes compartilhados
```

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
- Feedback: `x-shared.alert`, `x-shared.toast`, `x-shared.modal`, `x-shared.empty-state`

### Showcase interativo (só em local)

Acesse `/admin/dev/components` para ver todos os componentes com exemplos ao vivo.
Rota Livewire de exemplo: `/admin/dev/livewire`

### Outras regras

- Namespace: `x-admin.*` (admin), `x-shared.*` (ambos)
- Alpine.js só para interações visuais locais (toggle, collapse, tooltip)
- Livewire para qualquer interação que envolva dados do servidor
- Nunca CSS customizado — usar Tailwind classes

---

## 10. Livewire — Regras

```
app/Livewire/Admin/      ← Componentes Livewire do admin
resources/views/livewire/admin/  ← Views dos componentes
```

- Componente de exemplo: `app/Livewire/Admin/ExemploCounter.php`
- Use como tag: `<livewire:admin.exemplo-counter />`
- O layout já inclui `@livewireScripts` quando `$withLivewire` é true (passado via `<x-admin.layout :withLivewire="true">`)
- Ou inclua `@livewireScripts` manualmente na view se necessário

---

## 11. Banco de Dados

- **PostgreSQL 16**
- Valores monetários: colunas `INTEGER` (centavos)
- Índices em FKs, campos de filtro, status, datas
- Status operacional via campo `ativo` (liga/desliga; o registro segue visível)
- **Lixeira** via `deleted_at` (`SoftDeletes`), independente de `ativo`: excluir manda para a lixeira (some da listagem, restaurável). Trait genérico `App\Livewire\Concerns\ComLixeira`; 3 níveis de permissão por módulo (`deletar`→lixeira, `restaurar`, `excluir_permanente`→force-delete). Models implementam `App\Models\Contracts\UsaSoftDeletes`. Ver [`docs/lixeira.md`](docs/lixeira.md).
- `activity_log`: append-only (spatie/laravel-activitylog)

### Migrations de infra disponíveis (não alterar)

- `create_users_table` — tabela users padrão Laravel
- `create_cache_table` — cache Redis-backed
- `create_jobs_table` — filas
- `create_pulse_tables` — Pulse monitoring
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
- `barryvdh/laravel-dompdf` — PDFs
- `maatwebsite/excel` — exports CSV/Excel

**NPM (plugins Inspinia):**
ApexCharts · DataTables · Flatpickr · Choices.js · Inputmask · SortableJS · Dropzone ·
SweetAlert2 · TinyMCE · Quill · FullCalendar · Leaflet · Preline

---

## 13. Seeders e Usuários de Dev

```bash
php artisan migrate:fresh --seed
```

Cria:

- `admin@example.com` / `password` — role `super-admin`
- `gestor@example.com` / `password` — role `gestor`

O seed também marca o sistema como **instalado** (`GeneralSettings::instalado = true`), pulando o Setup Wizard. Em uma instalação limpa (`migrate` sem `--seed`), `instalado` é `false` e o primeiro acesso ao painel é redirecionado para `/admin/setup`.

Veja [`docs/configuracoes-sistema.md`](docs/configuracoes-sistema.md) para a arquitetura de configurações, branding dinâmico e como adicionar um novo grupo/aba.

---

## 14. Filas e Jobs (Horizon)

```
Fila default  → Jobs gerais
Fila emails   → E-mails transacionais
Fila exports  → Relatórios CSV/Excel
Fila pdf      → PDFs
```

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
make test        # Rodar testes
make lint        # Pint + Prettier
make quality     # Lint + PHPStan + Test
```

URLs locais: App `https://gdf-erp.ddev.site`, Horizon `/horizon`, Pulse `/pulse`, Mailpit `ddev mailpit`.

---

## 16. Iniciando um Novo Projeto com Este Boilerplate

1. Copiar este repositório
2. Rodar `./bin/init-project.sh` (renomeia marca/slug; ajuste o `name` do `.ddev/config.yaml`)
3. `cp .env.example .env`
4. `ddev start` (instala deps e roda migrate via hooks)
5. `make setup` (key, seed, assets Horizon/Pulse, build)
6. Acessar `https://<slug>.ddev.site/admin` → login com `admin@example.com` / `password`

### Adicionando um módulo de negócio

**Use o gerador** — ele cria a stack CRUD inteira já no padrão (migration, factory,
model, enum de status, DTO, FormRequests+Rules, actions, service, policy, Livewire
Index/Form/Table, views, teste) e injeta rotas + permissões:

```bash
php artisan make:modulo Cliente \
  --fields="nome:string, cnpj:cnpj, email:email:nullable, status:enum(ativo|inativo)" \
  --tenant
./vendor/bin/pint && npx prettier --write resources/views/livewire/admin/clientes/
php artisan migrate && php artisan access:sync
```

Depois, atribua as permissões (`/admin/acesso`) e adicione o item ao menu lateral.
Guia completo (tipos de campo, flags, customização dos stubs):
[`docs/criar-modulo.md`](docs/criar-modulo.md). O módulo **Exemplo** (em
`app/Livewire/Admin/Exemplos/`) é a referência viva — copie/apague à vontade. Os stubs ficam em
`stubs/modulo/`.

**Módulos reutilizáveis entre clientes** (produto HT2 ERP) viram pacotes Composer:
`php artisan make:modulo-pacote Rh` cria a casca e `make:modulo --module=Rh` gera o CRUD
dentro do pacote, sem editar o core. Ver [`ADR-0015`](docs/architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
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
4. Rodar `php artisan test` (testes passando)
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
