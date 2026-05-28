# Design — Setup Inicial + Padronização do Projeto

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas
**Data:** 2026-04-09
**Autor:** Leonardo (HT2ML TECH) + Claude Code
**Status:** Aprovado para execução

---

## 1. Contexto

Este documento descreve o plano de **setup de infraestrutura local** e **padronização de código** do projeto Portal ArtFinal, usando o **Linear** (via MCP conectado ao Claude Code) como ferramenta de gestão de issues.

O escopo cobre exclusivamente:

1. **Infra local funcional** — Docker/Laradock com PHP 8.4, Postgres 16, Redis, Mailpit, Horizon, Pulse e pgAdmin acessíveis.
2. **Todas as padronizações do `docs/02-CONVENTIONS.md`** — as 14 seções do arquivo, quando aplicáveis a setup (configs, pacotes, estrutura de pastas, hooks de git, formatadores, linters).

**Fora do escopo (YAGNI):** criação de migrations, models, controllers, layouts, guards de autenticação, rotas `admin.php`/`portal.php`/`webhook.php`, templates Inspinia/Preline, exceptions customizadas, middleware de webhook, Telescope. Todos esses itens pertencem à **Sprint 1+ do PRD** e serão criados quando a sprint relevante os exigir.

### 1.1 Estado atual do repositório

Mapeamento feito em 2026-04-09:

- **Laravel 13 skeleton** instalado (`composer.json`: framework, horizon, pulse, tinker, pint dev)
- **PHP ^8.2** no `composer.json` (conflito com `CLAUDE.md` que exige 8.4)
- **`package.json`** com template Inspinia completo (Tailwind 4, Preline, ApexCharts, Flatpickr, Choices, Inputmask, SortableJS, Dropzone, SweetAlert2 — Prettier sem plugin Blade)
- **`app/`** com apenas `Http/Controllers`, `Models/User.php`, `Providers` — faltam `Actions/`, `DTOs/`, `Enums/`, `Services/`, `Policies/`, `Observers/`, `Jobs/`, `Events/`, `Listeners/`, `Traits/`, `Exceptions/`
- **`routes/`** com apenas `web.php` e `console.php`
- **Sem `.git`** inicializado
- **Sem arquivos de config:** `pint.json`, `.prettierrc`, `.prettierignore`, `.eslintrc.json`, `phpstan.neon`, `.husky/`, `commitlint.config.js`
- **`.editorconfig`** e **`.gitattributes`** existem mas conteúdo precisa ser validado
- **`laradock/`** clonado (91 serviços disponíveis)
- **`Makefile`** com comandos essenciais (`up`, `down`, `bash`, `artisan`, `migrate`, `test`, `composer`, `npm`)
- **`docker-setup.sh`** automatizando boot inicial
- **`.env.example`** configurado para Postgres, Redis, Mailpit, Horizon, Pulse, `APP_LOCALE=pt_BR`
- **Linear MCP não está conectado** nesta sessão do Claude Code

### 1.2 Decisões de escopo confirmadas com o usuário

| Tema | Decisão |
|------|---------|
| Projects no Linear | **Apenas 1** ("Setup Inicial + Padronização"). Os outros 8 projects das fases do PRD serão criados quando chegarmos nelas. |
| Labels | **Conjunto completo** (22 labels das 3 categorias do `09-LINEAR-GUIDE.md §3`) criadas de uma vez. |
| Templates de issue | **3 templates** criados (Feature, Bug, Tarefa de Sprint). |
| Granularidade | **Híbrida** — issues-pai por tema com sub-issues atômicas (~2h cada, mas sem forçar quando o trabalho é naturalmente menor). |
| Abordagem de execução | **Infra First** — Docker sobe antes de qualquer config. Tudo roda dentro dos containers via `make`. |
| Commitlint | **Enforcement ativado** via Husky `commit-msg` hook. |
| Telescope | **Postergado** — instalar quando Sprint 2 começar. |
| Exceptions customizadas | **Postergadas** — criar na sprint que precisar. |
| Webhook middleware / rotas | **Postergados** — criar na Sprint 12. |
| Rotas `admin.php`/`portal.php` | **Postergadas** — criar na Sprint 1 do PRD. |
| Issue-pai de testes (#6) | **Eliminada** — Pest vira sub-issue 4.6 dentro de `#4 Pacotes & Estrutura Backend`. |

---

## 2. Estrutura macro no Linear

```
HT2ML TECH (workspace — já existe)
└── Team: Portal ArtFinal (identificador PAF)
    ├── Workflow: Backlog → Todo → In Progress → In Review → Done → Cancelled
    ├── Cycles: 1 semana, auto-rollover, início segunda
    ├── Labels (22 total):
    │   ├── Área:   portal, admin, gateway, infra, docs
    │   ├── Tipo:   feature, bug, refactor, chore, test, debt
    │   └── Módulo: mod:auth, mod:contratos, mod:produtos, mod:adesao,
    │               mod:financeiro, mod:emails, mod:acl, mod:auditoria,
    │               mod:relatorios, mod:config, mod:setup
    ├── Templates: Feature, Bug, Tarefa de Sprint
    ├── Projects:
    │   └── 🏗️ Setup Inicial + Padronização
    │       ├── Start: 2026-04-09
    │       └── Target: 2026-04-16 (fim do Cycle 1, 1 semana)
    └── Cycle 1 — "Sprint 01 — Setup + Padronização"
        └── 5 issues-pai · 21 sub-issues
```

---

## 3. Fase A — Checklist manual (pré-requisito)

Esta fase é executada **manualmente pelo Leonardo**, via UI do Linear + terminal, **antes** de iniciar a fase B. Claude Code não participa desta fase porque o MCP ainda não está conectado.

### 3.1 Passos

1. **Criar team PAF** no workspace HT2ML TECH
   - Nome: `Portal ArtFinal`
   - Identificador: `PAF`
   - Descrição: `Sistema de Gerenciamento de Formaturas`

2. **Configurar workflow**
   - Status: `Backlog → Todo → In Progress → In Review → Done → Cancelled`

3. **Ativar Cycles**
   - Duração: 1 semana
   - Início: segunda-feira
   - Auto-rollover: ativado
   - Cooldown: nenhum

4. **Criar project "Setup Inicial + Padronização"**
   - Start date: 2026-04-09
   - Target date: 2026-04-16 (fim do Cycle 1)
   - Description: link para este documento de design

5. **Criar Cycle 1** ("Sprint 01 — Setup + Padronização")

6. **Criar as 22 labels** nas 3 categorias:
   - **Área (5):** `portal`, `admin`, `gateway`, `infra`, `docs`
   - **Tipo (6):** `feature`, `bug`, `refactor`, `chore`, `test`, `debt`
   - **Módulo (11):** `mod:auth`, `mod:contratos`, `mod:produtos`, `mod:adesao`, `mod:financeiro`, `mod:emails`, `mod:acl`, `mod:auditoria`, `mod:relatorios`, `mod:config`, `mod:setup`

7. **Criar os 3 templates de issue** usando o conteúdo exato do `docs/09-LINEAR-GUIDE.md §5`:
   - Feature
   - Bug
   - Tarefa de Sprint

8. **Conectar GitHub integration**
   - Settings → Integrations → GitHub
   - Instalar GitHub App e autorizar repo `portalartfinal_v2`
   - Ativar: auto-link PRs, auto-close on merge, sync status (PR aberto → In Progress, merged → Done)

9. **Conectar Linear MCP ao Claude Code**
   ```bash
   claude mcp add --transport http linear-server https://mcp.linear.app/mcp
   claude
   /mcp
   # Autorizar via OAuth no browser que abrir
   ```

10. **Validar MCP**
    - Dentro da sessão Claude Code, pedir: `"liste os projects do team PAF"`
    - Deve retornar: `Setup Inicial + Padronização`

### 3.2 Critério de conclusão da Fase A

Quando os 10 passos acima estiverem concluídos, o Leonardo chama o Claude Code na mesma sessão e inicia a Fase B.

---

## 4. Fase B — Bootstrap do Cycle via MCP (Claude Code)

Com o MCP conectado, o Claude Code executa **uma única vez** o bootstrap das issues no Linear.

### 4.1 O que o Claude Code faz

1. Cria as **5 issues-pai** no project "Setup Inicial + Padronização", vinculadas ao Cycle 1
   - Labels em cada pai: `infra`, `chore`, `mod:setup`
   - Prioridade: **High** para #1 e #3 (bloqueantes), **Medium** para #2, #4 e #5

2. Cria as **21 sub-issues** como sub-issues de cada pai, usando o template "Tarefa de Sprint" adaptado

3. Configura **dependências** (`blockedBy`):
   - #3 blockedBy #2 (Husky precisa de git init)
   - #4 blockedBy #1 (composer require precisa de workspace container)
   - #5 blockedBy #1 e #4 (Horizon config precisa de composer + Redis rodando)

4. Adiciona **comentários iniciais** em cada issue-pai com links diretos para as seções do `docs/02-CONVENTIONS.md` e `docs/03-TOOLS-AND-PACKAGES.md` relevantes

5. Mostra o **resumo final** no chat com quantidade de issues criadas, IDs PAF-X e link do Cycle

---

## 5. As 5 issues-pai + 21 sub-issues

### 5.1 Issue-pai #1 — 🐳 Infra Local

**Objetivo:** Docker/Laradock rodando, Laravel acessível em `http://localhost`, PHP 8.4 no workspace.

**Labels:** `infra`, `chore`, `mod:setup`
**Prioridade:** High
**BlockedBy:** nenhuma

| # | Sub-issue | Descrição |
|---|-----------|-----------|
| 1.1 | Configurar `laradock/.env` para PHP 8.4 + Postgres 16 + Redis | Ajustar `PHP_VERSION=8.4`, `WORKSPACE_PHP_VERSION=8.4`, extensões PHP (`pdo_pgsql`, `redis`, `bcmath`, `gd`, `intl`, `zip`), `POSTGRES_VERSION=16`, timezone `America/Sao_Paulo`, `WORKSPACE_INSTALL_NODE=true`, `WORKSPACE_INSTALL_YARN=true`. |
| 1.2 | Corrigir `composer.json` para PHP ^8.4 | Trocar `"php": "^8.2"` → `"php": "^8.4"`. |
| 1.3 | Rodar `./docker-setup.sh` e validar boot | Executar o script existente. Validar `make status` mostrando todos os containers up. `make bash` → `php -v` deve retornar 8.4.x. Validar `psql` e `redis-cli ping`. |
| 1.4 | Validar URLs + criar `docs/INFRA.md` | Smoke test em: `http://localhost` (app), `/horizon`, `/pulse`, `http://localhost:5050` (pgAdmin), `http://localhost:8125` (Mailpit). Criar `docs/INFRA.md` com URLs, portas, comandos `make` e troubleshooting de boot. |

**Critério de aceite:**
- [ ] `make up` sobe todos os serviços sem erro
- [ ] `make bash` → `php -v` retorna PHP 8.4.x
- [ ] As 5 URLs retornam 200 no browser
- [ ] `php artisan tinker` conecta no Postgres e Redis sem erro

---

### 5.2 Issue-pai #2 — 📝 Git & Commits

**Objetivo:** repositório versionado, estratégia de branches ativa, Conventional Commits enforced.

**Labels:** `infra`, `chore`, `mod:setup`
**Prioridade:** Medium
**BlockedBy:** nenhuma

| # | Sub-issue | Descrição |
|---|-----------|-----------|
| 2.1 | `git init` + primeiro commit + branch `develop` | `git init`, revisar e expandir `.gitignore` (adicionar `/.husky/_`, `.phpunit.result.cache`, `.phpstan.cache`, `coverage/`, `.idea/`, `.DS_Store`). Commit inicial `chore(infra): inicializa repositório`. Criar branch `develop` a partir de `main`. Configurar `develop` como default local. |
| 2.2 | Criar `commitlint.config.js` | Config com tipos e escopos exatos do `02-CONVENTIONS.md §1.2` e `§1.3`. Tipos: `feat/fix/refactor/docs/style/test/chore/perf/ci/revert`. Escopos: `admin/portal/gateway/financeiro/adesao/auth/infra/models/docs/ui`. `subject-case` permissivo (PT-BR). Max 72 chars na primeira linha. O hook `commit-msg` que ativa isso é criado em 3.5. |
| 2.3 | Revisar `.editorconfig` e `.gitattributes` | Preencher `.editorconfig` com o conteúdo exato do `08-PADRONIZACAO-SPRINTS-AGENTES.md §2.1` (utf-8, lf, indent_size 4, overrides JS/CSS 2, Makefile tab). `.gitattributes` garantir `* text=auto eol=lf`. |

**Critério de aceite:**
- [ ] `git log` mostra o commit inicial
- [ ] `git branch` mostra `main` e `develop`
- [ ] `.editorconfig` presente e preenchido
- [ ] `commitlint.config.js` presente (ainda não ativo — ativação em #3)

---

### 5.3 Issue-pai #3 — ✨ Qualidade de Código

**Objetivo:** Pint, Prettier, ESLint, PHPStan e Husky rodando. Formatação e lint automáticos no commit. Ativa o commitlint criado em #2.

**Labels:** `infra`, `chore`, `mod:setup`
**Prioridade:** High
**BlockedBy:** #2

| # | Sub-issue | Descrição |
|---|-----------|-----------|
| 3.1 | Criar `pint.json` | Conteúdo exato do `08-PADRONIZACAO-SPRINTS-AGENTES.md §2.2`: preset `laravel`, `declare_strict_types: true`, `array_syntax: short`, `ordered_imports`, `ordered_class_elements`, exclude `node_modules/vendor/storage/bootstrap/cache`. |
| 3.2 | Criar `.prettierrc` + `.prettierignore` + instalar plugin Blade | `.prettierrc` do §2.3 (`printWidth: 120`, `singleQuote`, `tabWidth: 4`, `trailingComma: all`, plugins `prettier-plugin-blade` + `prettier-plugin-tailwindcss`, overrides Blade/JS/CSS/JSON). `.prettierignore` do §2.4. Instalar `prettier-plugin-blade` via `make npm install --save-dev prettier-plugin-blade`. |
| 3.3 | Instalar e configurar PHPStan (Larastan) level 6 | `make composer require --dev larastan/larastan`. Criar `phpstan.neon` do §2.5 (level 6, paths `app/`, exclude `app/Console/Kernel.php`). Rodar `./vendor/bin/phpstan analyse` uma vez para confirmar que passa. |
| 3.4 | Instalar ESLint + criar `.eslintrc.json` | `make npm install --save-dev eslint eslint-config-prettier`. Criar `.eslintrc.json` do §2.6 (env browser/es2024/node, extends `eslint:recommended` + `prettier`, rules `no-console: warn`, `no-unused-vars: warn`, `prefer-const: error`, `no-var: error`). |
| 3.5 | Instalar Husky + lint-staged + ativar hooks (pre-commit e commit-msg) | `make npm install --save-dev husky lint-staged @commitlint/cli @commitlint/config-conventional` + `npx husky init`. Criar `.husky/pre-commit` com `npx lint-staged`. Criar `.husky/commit-msg` com `npx --no-install commitlint --edit $1`. Adicionar bloco `lint-staged` no `package.json` (§3.3): `*.php → pint --dirty`, `*.blade.php → prettier`, `*.{js,ts} → eslint --fix + prettier`, `*.{css,scss,json,md} → prettier`. Adicionar scripts npm do §4: `format`, `format:check`, `lint:php`, `lint:js`, `analyse`, `test`, `quality`. Fazer commit de teste para validar. |

**Critério de aceite:**
- [ ] `make quality` roda sem erro (`pint --test` + `phpstan analyse` + `prettier --check` + `php artisan test`)
- [ ] Commit com mensagem fora do padrão Conventional Commits é **recusado** pelo Husky
- [ ] Commit com PHP mal formatado é **auto-corrigido** pelo Husky pre-commit
- [ ] `php --version` dentro do container workspace = 8.4.x

---

### 5.4 Issue-pai #4 — 📦 Pacotes & Estrutura Backend (+ Testes base)

**Objetivo:** todos os pacotes do `03-TOOLS-AND-PACKAGES.md` instalados, árvore de pastas do `01-ARCHITECTURE-GUIDE.md` criada, `preventLazyLoading` ativo, pt_BR localização publicada, Pest rodando com estrutura de pastas.

**Labels:** `infra`, `chore`, `mod:setup`
**Prioridade:** Medium
**BlockedBy:** #1

| # | Sub-issue | Descrição |
|---|-----------|-----------|
| 4.1 | Instalar pacotes Composer essenciais | Via `make bash`: `composer require livewire/livewire spatie/laravel-permission spatie/laravel-activitylog barryvdh/laravel-dompdf maatwebsite/excel laravellegends/pt-br-validator guzzlehttp/guzzle`. Publicar: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` e idem para `activitylog`. **NÃO** instalar `laravel/sanctum` agora. |
| 4.2 | Instalar pacotes Composer dev-only | `composer require --dev barryvdh/laravel-debugbar pestphp/pest pestphp/pest-plugin-laravel`. **Telescope postergado** (instalar na Sprint 2). |
| 4.3 | Auditar pacotes NPM do Inspinia | Conferir com `npm ls` que os pacotes do `03-TOOLS-AND-PACKAGES.md §2.2` estão presentes (ApexCharts, Flatpickr, Choices, Inputmask, SortableJS, Dropzone, SweetAlert2, TinyMCE). Instalar apenas os que faltarem. |
| 4.4 | Criar árvore de pastas do `01-ARCHITECTURE-GUIDE.md` | Criar as pastas em `app/` com `.gitkeep`: `Actions/`, `DTOs/`, `Enums/`, `Services/`, `Policies/`, `Observers/`, `Jobs/`, `Events/`, `Listeners/`, `Traits/`, `Exceptions/`, `Http/Middleware/`, `Http/Requests/`, `Console/Commands/`. Criar também `app/Livewire/Admin/` e `app/Livewire/Portal/`. **Sem arquivos reais dentro.** |
| 4.5 | Configurar `preventLazyLoading` + pt_BR localization | Em `app/Providers/AppServiceProvider::boot()`: adicionar `Model::preventLazyLoading(! app()->isProduction())`. Instalar `lucascudo/laravel-pt-br-localization` (`composer require --dev` + `php artisan vendor:publish --tag=laravel-pt-br-localization`). Verificar `APP_LOCALE=pt_BR` no `.env.example`. |
| 4.6 | Configurar Pest + estrutura de pastas de testes + smoke test | `php artisan pest:install` (ou equivalente do plugin Laravel). Criar pastas `tests/Feature/{Admin,Portal,Webhook}` e `tests/Unit/{Services,Models}` com `.gitkeep`. Atualizar `phpunit.xml` se necessário. Criar `tests/Feature/EnvironmentTest.php` com `it('boots the application', fn () => expect(app()->environment('testing'))->toBeTrue())`. Rodar `make test` → deve passar. |

**Critério de aceite:**
- [ ] `composer show | grep spatie` lista `laravel-permission` e `laravel-activitylog`
- [ ] `composer show | grep livewire` retorna `livewire/livewire`
- [ ] `tree app/ -L 1 -d` mostra todas as pastas novas com `.gitkeep`
- [ ] `make test` passa o smoke test do Pest
- [ ] `php artisan tinker` → `\App\Models\User::all()->first()->someRelation` dispara exception de lazy loading

---

### 5.5 Issue-pai #5 — ⚙️ Filas, Cache, Logs

**Objetivo:** Horizon com as 6 filas nomeadas, canais de log customizados, Redis validado como cache.

**Labels:** `infra`, `chore`, `mod:setup`
**Prioridade:** Medium
**BlockedBy:** #1 (Docker rodando), #4 (para evitar race condition no autoloader durante instalação de pacotes em paralelo)

| # | Sub-issue | Descrição |
|---|-----------|-----------|
| 5.1 | Configurar `config/horizon.php` com as 6 filas | Editar `environments.production` e `environments.local`. Supervisor `supervisor-default` com `queue: ['gateway', 'webhooks', 'default', 'emails', 'exports', 'pdf']`, `balance: auto`, `maxProcesses: 10`, `tries: 3`. Segundo supervisor `supervisor-high-priority` só para `gateway` e `webhooks` com `tries: 3` e `backoff: [10, 60, 300]` (do `02-CONVENTIONS.md §10.2`). Validar: abrir `/horizon` e ver as 6 filas. |
| 5.2 | Configurar `config/logging.php` com canais custom | Adicionar 3 canais do §7.1: `gateway` (daily, 30 days), `webhook` (daily, 30 days), `audit` (daily, 90 days). Adicionar ao `stack` para manter `Log::info()` default funcionando. Smoke test: `Log::channel('gateway')->info('test')` em tinker → arquivo criado em `storage/logs/gateway-YYYY-MM-DD.log`. |
| 5.3 | Validar cache Redis + criar `docs/CACHE-PREFIXOS.md` | Conferir `.env`: `CACHE_STORE=redis`, `REDIS_CLIENT=phpredis`. Rodar `Cache::put('config:test', 'ok', 60)` + `Cache::get('config:test')` em tinker. Criar `docs/CACHE-PREFIXOS.md` curto listando os prefixos padronizados do §9.3 (`config:`, `acl:`, `programacao:`, `dashboard:`, `contrato:`) como referência para implementação futura de Services. |

**Critério de aceite:**
- [ ] `/horizon` lista as 6 filas com seus supervisors
- [ ] `Log::channel('gateway')->info('x')` cria o arquivo correto em `storage/logs/`
- [ ] `Cache::put` / `Cache::get` funciona via Redis
- [ ] `docs/CACHE-PREFIXOS.md` existe e lista os 5 prefixos

---

## 6. Fase C — Execução intercalada

Após o bootstrap da Fase B, Leonardo e Claude Code executam as issues de forma intercalada. Ciclo por sub-issue:

```
1. Leonardo:  "pega a PAF-X, mova para In Progress"
2. Claude:    lê detalhes da issue via MCP, executa o trabalho técnico
              (edita arquivos, roda `make composer require`, cria configs)
3. Leonardo:  valida o diff/resultado
4. Claude:    commita com mensagem "chore(infra): <descrição> — PAF-X"
5. Claude:    move para In Review (ou Done se sem revisão)
6. Quando todas as sub-issues de uma pai estiverem Done,
   Leonardo fecha a pai manualmente
```

### 6.1 Ordem cronológica sugerida (sequencial)

Respeitando dependências:

```
1. #1 Infra Local
2. #2 Git & Commits
3. #4 Pacotes + Backend + Testes (pode começar em paralelo com #3 depois de #2)
4. #3 Qualidade de Código
5. #5 Filas, Cache, Logs
```

Grafo de dependências:

```
#1 Infra ──┬──→ #4 Pacotes ──→ #5 Filas/Cache/Logs ──→ ✅ Cycle fechado
           │                          ↑
#2 Git ────┴──→ #3 Qualidade ─────────┘
```

---

## 7. Critério de aceite do Cycle 1 (consolidado)

Todos os itens abaixo devem estar verdes para considerar o Cycle 1 concluído:

- [ ] `make up` sobe todos os serviços Docker sem erro
- [ ] `make bash` → `php -v` retorna 8.4.x
- [ ] Todas as 5 URLs respondem 200 (`/`, `/horizon`, `/pulse`, `:5050`, `:8125`)
- [ ] `git log` mostra histórico válido em `develop`
- [ ] Commit fora do padrão Conventional Commits é **recusado** pelo Husky
- [ ] Commit com PHP mal formatado é **auto-corrigido** pelo Husky
- [ ] `make quality` passa (`pint --test` + `phpstan analyse` + `prettier --check` + `php artisan test`)
- [ ] `/horizon` lista as 6 filas
- [ ] `Log::channel('gateway')->info('x')` cria arquivo de log correto
- [ ] `composer show` lista todos os pacotes essenciais do `docs/03-TOOLS-AND-PACKAGES.md §1.1`
- [ ] `tree app/ -L 1 -d` mostra a árvore de pastas do `docs/01-ARCHITECTURE-GUIDE.md`
- [ ] `make test` passa o smoke test do Pest
- [ ] Todas as issues-pai PAF do Cycle 1 estão `Done`

---

## 8. Resumo quantitativo

| Item | Quantidade |
|------|:----------:|
| Projects criados no Linear | 1 |
| Labels criadas | 22 |
| Templates de issue criados | 3 |
| Cycles criados | 1 |
| Issues-pai | 5 |
| Sub-issues | 21 |
| **Total de issues no Cycle 1** | **26** |
| Arquivos de config criados | 6 (`pint.json`, `.prettierrc`, `.prettierignore`, `phpstan.neon`, `.eslintrc.json`, `commitlint.config.js`) |
| Hooks de git criados | 2 (`pre-commit`, `commit-msg`) |
| Pacotes Composer adicionados | 7 essenciais + 4 dev + 1 localization |
| Pacotes NPM adicionados | 7 (husky, lint-staged, @commitlint/cli, @commitlint/config-conventional, eslint, eslint-config-prettier, prettier-plugin-blade) + auditoria de plugins Inspinia |
| Pastas criadas em `app/` | 14 |
| Docs novos criados | 3 (`docs/INFRA.md`, `docs/CACHE-PREFIXOS.md`, este design) |

---

## 9. Itens postergados (explícitos — não são "esquecimento")

| Item | Quando criar |
|------|--------------|
| `laravel/telescope` + publish | Sprint 2 do PRD (quando já houver migrations do projeto) |
| `app/Exceptions/BusinessRuleException.php` + hierarquia | Sprint que precisar da primeira exceção (Sprint 7 para `FinanceiroException`, Sprint 9 para `AdesaoException`, Sprint 12 para `PagamentoException`) |
| `VerifyWebhookSignature` middleware | Sprint 12 (integração Itaú) |
| `routes/webhook.php` | Sprint 12 |
| `routes/admin.php` | Sprint 1 do PRD |
| `routes/portal.php` | Sprint 1 do PRD |
| Layouts Blade admin/portal | Sprint 1 do PRD |
| Guards de auth admin/portal | Sprint 1 do PRD |
| Template Inspinia integrado no layout | Sprint 1 do PRD |
| Decisão Preline UI vs Tailwind puro para portal | Sprint 4 do PRD |
| Templates de e-mail + tabela `email_logs` | Sprint 14 do PRD |
| Restante dos 8 projects do Linear (Portal Adesão, Gateway Itaú, etc.) | Quando iniciar cada fase |

---

## 10. Referências

- `CLAUDE.md` — contexto geral do projeto
- `docs/01-ARCHITECTURE-GUIDE.md` — árvore de pastas e organização
- `docs/02-CONVENTIONS.md` — fonte principal das padronizações deste design
- `docs/03-TOOLS-AND-PACKAGES.md` — justificativas de pacotes Composer e NPM
- `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md` — conteúdo literal dos arquivos de config (Pint, Prettier, PHPStan, ESLint, Husky)
- `docs/09-LINEAR-GUIDE.md` — setup do Linear, labels, templates, MCP
- `docs/PRD_Sistema_Formatura_v3.1.0.md §20.3` — Sprint 1 do PRD (setup do projeto e estrutura base)
