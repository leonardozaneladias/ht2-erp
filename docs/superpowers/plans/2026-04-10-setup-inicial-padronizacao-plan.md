# Setup Inicial + Padronização — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Subir infraestrutura local (Docker/Laradock com PHP 8.4, Postgres 16, Redis, Mailpit, Horizon, Pulse, pgAdmin), aplicar todas as padronizações do `docs/02-CONVENTIONS.md`, e gerenciar o trabalho no Linear via MCP.

**Architecture:** Abordagem **Infra First** — Docker sobe antes de qualquer config. Toda padronização (Pint, Prettier, PHPStan, ESLint, Husky, commitlint) roda dentro dos containers via `make`. Linear gerencia issues via MCP remoto (`mcp.linear.app/mcp`) já conectado na sessão pelo usuário. Ordem sequencial de execução: `#1 Infra → #2 Git → #3 Qualidade → #4 Pacotes/Backend/Pest → #5 Filas/Cache/Logs`.

**Tech Stack:** Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis · Docker via Laradock · Livewire 3 · Tailwind CSS 4 · Pest 3 · Laravel Pint · Prettier · PHPStan (Larastan) level 6 · ESLint · Husky · lint-staged · commitlint · Linear MCP

**Pre-requisitos (Fase A — executado manualmente pelo usuário antes de começar este plano):**
1. Team PAF criado no workspace HT2ML TECH
2. Project "Setup Inicial + Padronização" criado
3. Cycle 1 aberto ("Sprint 01 — Setup + Padronização")
4. 22 labels criadas
5. 3 templates criados
6. GitHub integration conectada
7. Linear MCP conectado ao Claude Code (`claude mcp add --transport http linear-server https://mcp.linear.app/mcp` + `/mcp` + OAuth)
8. Validação: `"liste os projects do team PAF"` retorna "Setup Inicial + Padronização"

**Referência completa:** `docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md`

---

## File Structure Overview

Arquivos criados/modificados neste plano:

**Criados:**
- `pint.json` — Laravel Pint config (Task 9)
- `.prettierrc` — Prettier config (Task 10)
- `.prettierignore` — Prettier ignore (Task 10)
- `phpstan.neon` — PHPStan/Larastan config (Task 11)
- `.eslintrc.json` — ESLint config (Task 12)
- `commitlint.config.js` — Conventional Commits config (Task 7)
- `.husky/pre-commit` — lint-staged hook (Task 13)
- `.husky/commit-msg` — commitlint hook (Task 13)
- `docs/INFRA.md` — Docs de infraestrutura local (Task 5)
- `docs/CACHE-PREFIXOS.md` — Referência de prefixos de cache (Task 22)
- `tests/Feature/EnvironmentTest.php` — Smoke test do Pest (Task 19)
- `tests/Feature/{Admin,Portal,Webhook}/.gitkeep` — Estrutura de pastas de teste (Task 19)
- `tests/Unit/{Services,Models}/.gitkeep` — idem (Task 19)
- `app/{Actions,DTOs,Enums,Services,Policies,Observers,Jobs,Events,Listeners,Traits,Exceptions}/.gitkeep` — Árvore de pastas (Task 17)
- `app/Livewire/{Admin,Portal}/.gitkeep` — idem (Task 17)

**Modificados:**
- `laradock/.env` — PHP 8.4, Postgres 16, extensões PHP (Task 2)
- `composer.json` — PHP `^8.4`, novos pacotes (Tasks 3, 14, 15, 18)
- `package.json` — lint-staged block, scripts npm (Tasks 10, 12, 13)
- `.gitignore` — entradas adicionais (Task 6)
- `.editorconfig` — conteúdo padronizado (Task 8)
- `.gitattributes` — `* text=auto eol=lf` (Task 8)
- `app/Providers/AppServiceProvider.php` — `preventLazyLoading` + pt_BR boot (Task 18)
- `config/horizon.php` — 6 filas + supervisors (Task 20)
- `config/logging.php` — canais gateway/webhook/audit (Task 21)
- `.env.example` — validar `CACHE_STORE=redis` (Task 22)
- `phpunit.xml` — suites de teste (Task 19)

---

## Task 1: Linear MCP Bootstrap — criar as 5 issues-pai + 21 sub-issues no Cycle 1

**Files:** nenhum (só MCP calls ao Linear)

**Pre-requisito crítico:** Linear MCP **precisa estar conectado** nesta sessão. Validar com uma chamada MCP simples antes de começar (ex.: listar projects). Se não estiver conectado, **parar o plano** e pedir ao usuário para executar a Fase A §7-8 do spec.

- [ ] **Step 1.1: Validar que Linear MCP está conectado**

Execute via MCP: listar projects do team PAF do workspace HT2ML TECH.

Expected: retorna uma lista incluindo `Setup Inicial + Padronização`.

Se falhar: parar, instruir usuário a rodar `claude mcp add --transport http linear-server https://mcp.linear.app/mcp` e `/mcp` com OAuth.

- [ ] **Step 1.2: Obter IDs do project e do Cycle 1**

Via MCP: buscar o project "Setup Inicial + Padronização" e o cycle ativo do team PAF. Guardar os IDs retornados (`PROJECT_ID` e `CYCLE_ID`) para usar nas criações seguintes.

- [ ] **Step 1.3: Criar issue-pai #1 — 🐳 Infra Local**

Via MCP, criar issue no team PAF com:
- **Title:** `🐳 Infra Local (Laradock + Serviços)`
- **Project:** `PROJECT_ID`
- **Cycle:** `CYCLE_ID`
- **Labels:** `infra`, `chore`, `mod:setup`
- **Priority:** High (urgência 2)
- **Description:**
  ```
  Objetivo: Docker/Laradock rodando, Laravel acessível em http://localhost, PHP 8.4 no workspace.

  Sub-issues:
  - [ ] 1.1 Configurar laradock/.env para PHP 8.4 + Postgres 16 + Redis
  - [ ] 1.2 Corrigir composer.json para PHP ^8.4
  - [ ] 1.3 Rodar ./docker-setup.sh e validar boot
  - [ ] 1.4 Validar URLs + criar docs/INFRA.md

  Critério de aceite:
  - [ ] `make up` sobe todos os serviços sem erro
  - [ ] `make bash` → `php -v` retorna PHP 8.4.x
  - [ ] As 5 URLs retornam 200 no browser
  - [ ] `php artisan tinker` conecta no Postgres e Redis sem erro

  Ref: docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md §5.1
  ```

Guardar o `issue.id` retornado como `PARENT_1_ID`.

- [ ] **Step 1.4: Criar as 4 sub-issues da issue-pai #1**

Via MCP, criar 4 issues vinculadas a `PARENT_1_ID` (parent relationship), todas no mesmo project/cycle/labels da pai, priority Medium:

**Sub-issue 1.1** — Title: `[1.1] Configurar laradock/.env para PHP 8.4 + Postgres 16`
Description:
```
Ajustar laradock/.env:
- PHP_VERSION=8.4
- WORKSPACE_PHP_VERSION=8.4
- Extensões PHP: pdo_pgsql, redis, bcmath, gd, intl, zip
- POSTGRES_VERSION=16
- Timezone: America/Sao_Paulo
- WORKSPACE_INSTALL_NODE=true
- WORKSPACE_INSTALL_YARN=true

Ref: plan Task 2
```

**Sub-issue 1.2** — Title: `[1.2] Corrigir composer.json para PHP ^8.4`
Description:
```
Trocar "php": "^8.2" por "php": "^8.4" no require do composer.json.

Ref: plan Task 3
```

**Sub-issue 1.3** — Title: `[1.3] Rodar ./docker-setup.sh e validar boot`
Description:
```
Executar docker-setup.sh existente.
Validar:
- make status mostra todos containers up
- make bash → php -v retorna 8.4.x
- psql conecta
- redis-cli ping retorna PONG

Ref: plan Task 4
```

**Sub-issue 1.4** — Title: `[1.4] Validar URLs + criar docs/INFRA.md`
Description:
```
Smoke test das 5 URLs:
- http://localhost (app)
- http://localhost/horizon
- http://localhost/pulse
- http://localhost:5050 (pgAdmin)
- http://localhost:8125 (Mailpit)

Criar docs/INFRA.md com URLs, portas, comandos make e troubleshooting.

Ref: plan Task 5
```

- [ ] **Step 1.5: Criar issue-pai #2 — 📝 Git & Commits**

Via MCP, criar issue:
- **Title:** `📝 Git & Commits`
- **Project:** `PROJECT_ID`, **Cycle:** `CYCLE_ID`
- **Labels:** `infra`, `chore`, `mod:setup`
- **Priority:** Medium
- **Description:**
  ```
  Objetivo: repositório versionado, estratégia de branches ativa, Conventional Commits enforced.

  Sub-issues:
  - [ ] 2.1 git init + primeiro commit + branch develop
  - [ ] 2.2 Criar commitlint.config.js
  - [ ] 2.3 Revisar .editorconfig e .gitattributes

  Ref: docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md §5.2
  ```

Guardar `PARENT_2_ID`.

- [ ] **Step 1.6: Criar as 3 sub-issues da issue-pai #2**

Via MCP, vinculadas a `PARENT_2_ID`, labels infra/chore/mod:setup, priority Medium:

**Sub-issue 2.1** — `[2.1] git init + primeiro commit + branch develop`
Description: `git init, expandir .gitignore (/.husky/_, .phpunit.result.cache, .phpstan.cache, coverage/, .idea/, .DS_Store), commit inicial "chore(infra): inicializa repositório", criar branch develop. Ref: plan Task 6`

**Sub-issue 2.2** — `[2.2] Criar commitlint.config.js`
Description: `Config com tipos (feat/fix/refactor/docs/style/test/chore/perf/ci/revert) e escopos (admin/portal/gateway/financeiro/adesao/auth/infra/models/docs/ui) do 02-CONVENTIONS.md §1.2-§1.3. Ref: plan Task 7`

**Sub-issue 2.3** — `[2.3] Revisar .editorconfig e .gitattributes`
Description: `Preencher .editorconfig com conteúdo do 08-PADRONIZACAO-SPRINTS-AGENTES.md §2.1 e .gitattributes com * text=auto eol=lf. Ref: plan Task 8`

- [ ] **Step 1.7: Criar issue-pai #3 — ✨ Qualidade de Código**

Via MCP:
- **Title:** `✨ Qualidade de Código (Pint/Prettier/PHPStan/ESLint/Husky)`
- **Project/Cycle/Labels:** mesmos
- **Priority:** High
- **Description:**
  ```
  Objetivo: Pint, Prettier, ESLint, PHPStan e Husky rodando. Formatação e lint automáticos no commit. Ativa commitlint criado em #2.

  Sub-issues:
  - [ ] 3.1 Criar pint.json
  - [ ] 3.2 Criar .prettierrc + .prettierignore + instalar plugin Blade
  - [ ] 3.3 Instalar PHPStan (Larastan) level 6
  - [ ] 3.4 Instalar ESLint + .eslintrc.json
  - [ ] 3.5 Instalar Husky + lint-staged + ativar hooks

  Dependencies: blockedBy #2

  Ref: docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md §5.3
  ```

Guardar `PARENT_3_ID`. Adicionar relação `blockedBy: PARENT_2_ID` via MCP update.

- [ ] **Step 1.8: Criar as 5 sub-issues da issue-pai #3**

Via MCP, vinculadas a `PARENT_3_ID`:

**3.1** — `[3.1] Criar pint.json` — `Conteúdo exato do 08-PADRONIZACAO-SPRINTS-AGENTES.md §2.2. Ref: plan Task 9`

**3.2** — `[3.2] Criar .prettierrc + .prettierignore + instalar prettier-plugin-blade` — `Config do §2.3 + §2.4. make npm install --save-dev prettier-plugin-blade. Ref: plan Task 10`

**3.3** — `[3.3] Instalar PHPStan (Larastan) level 6` — `make composer require --dev larastan/larastan + phpstan.neon do §2.5. Ref: plan Task 11`

**3.4** — `[3.4] Instalar ESLint + .eslintrc.json` — `make npm install --save-dev eslint eslint-config-prettier + config do §2.6. Ref: plan Task 12`

**3.5** — `[3.5] Instalar Husky + lint-staged + ativar hooks` — `husky, lint-staged, @commitlint/cli, @commitlint/config-conventional. Hooks pre-commit e commit-msg. lint-staged + scripts npm no package.json. Ref: plan Task 13`

- [ ] **Step 1.9: Criar issue-pai #4 — 📦 Pacotes & Estrutura Backend**

Via MCP:
- **Title:** `📦 Pacotes & Estrutura Backend (+ Testes base)`
- **Priority:** Medium
- **Description:**
  ```
  Objetivo: pacotes Composer do 03-TOOLS-AND-PACKAGES.md instalados, árvore de pastas do 01-ARCHITECTURE-GUIDE.md criada, preventLazyLoading ativo, pt_BR publicado, Pest rodando.

  Sub-issues:
  - [ ] 4.1 Instalar pacotes Composer essenciais
  - [ ] 4.2 Instalar pacotes Composer dev-only
  - [ ] 4.3 Auditar pacotes NPM do Inspinia
  - [ ] 4.4 Criar árvore de pastas em app/
  - [ ] 4.5 Configurar preventLazyLoading + pt_BR localization
  - [ ] 4.6 Configurar Pest + estrutura de pastas de testes + smoke test

  Dependencies: blockedBy #1

  Ref: docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md §5.4
  ```

Guardar `PARENT_4_ID`. Adicionar `blockedBy: PARENT_1_ID`.

- [ ] **Step 1.10: Criar as 6 sub-issues da issue-pai #4**

Via MCP:

**4.1** — `[4.1] Instalar pacotes Composer essenciais` — `composer require livewire/livewire spatie/laravel-permission spatie/laravel-activitylog barryvdh/laravel-dompdf maatwebsite/excel laravellegends/pt-br-validator guzzlehttp/guzzle. Publicar vendor dos spatie. Ref: plan Task 14`

**4.2** — `[4.2] Instalar pacotes Composer dev-only` — `composer require --dev barryvdh/laravel-debugbar pestphp/pest pestphp/pest-plugin-laravel. Telescope postergado. Ref: plan Task 15`

**4.3** — `[4.3] Auditar pacotes NPM do Inspinia` — `npm ls e confirmar §2.2 do 03-TOOLS-AND-PACKAGES.md presente. Instalar faltantes. Ref: plan Task 16`

**4.4** — `[4.4] Criar árvore de pastas do 01-ARCHITECTURE-GUIDE.md` — `14 pastas em app/ com .gitkeep. Ref: plan Task 17`

**4.5** — `[4.5] preventLazyLoading + pt_BR localization` — `Model::preventLazyLoading no AppServiceProvider::boot + lucascudo/laravel-pt-br-localization. Ref: plan Task 18`

**4.6** — `[4.6] Configurar Pest + estrutura tests + smoke test` — `pest:install + pastas tests/Feature/{Admin,Portal,Webhook} + tests/Unit/{Services,Models} + EnvironmentTest.php. Ref: plan Task 19`

- [ ] **Step 1.11: Criar issue-pai #5 — ⚙️ Filas, Cache, Logs**

Via MCP:
- **Title:** `⚙️ Filas, Cache, Logs`
- **Priority:** Medium
- **Description:**
  ```
  Objetivo: Horizon com 6 filas nomeadas, canais de log custom, Redis validado.

  Sub-issues:
  - [ ] 5.1 Configurar config/horizon.php com as 6 filas
  - [ ] 5.2 Configurar config/logging.php com canais custom
  - [ ] 5.3 Validar cache Redis + criar docs/CACHE-PREFIXOS.md

  Dependencies: blockedBy #1 (Docker), #4 (evitar race no autoloader)

  Ref: docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md §5.5
  ```

Guardar `PARENT_5_ID`. Adicionar `blockedBy: PARENT_1_ID, PARENT_4_ID`.

- [ ] **Step 1.12: Criar as 3 sub-issues da issue-pai #5**

**5.1** — `[5.1] Configurar config/horizon.php com as 6 filas` — `supervisor-default (balance auto, tries 3) + supervisor-high-priority (tries 3, backoff [10,60,300]) para gateway e webhooks. Ref: plan Task 20`

**5.2** — `[5.2] Configurar config/logging.php com canais custom` — `Canais gateway (30d), webhook (30d), audit (90d). Ref: plan Task 21`

**5.3** — `[5.3] Validar Redis + criar docs/CACHE-PREFIXOS.md` — `Cache::put/get em tinker. Docs com 5 prefixos (config:, acl:, programacao:, dashboard:, contrato:). Ref: plan Task 22`

- [ ] **Step 1.13: Validar bootstrap completo**

Via MCP, listar todas as issues do Cycle 1 do team PAF. Expected:
- 5 issues-pai visíveis
- 21 sub-issues vinculadas (4+3+5+6+3)
- Total: 26 issues
- Dependências corretas: #3 blockedBy #2, #4 blockedBy #1, #5 blockedBy #1 e #4

Se o total for diferente de 26, identificar a diferença e corrigir.

- [ ] **Step 1.14: Commit (ainda não, precisa do git init)**

**Nota:** Task 1 não gera commit porque git ainda não foi inicializado (isso é Task 6). O trabalho da Task 1 existe apenas no Linear, não no filesystem.

---

## Task 2: Configurar `laradock/.env` para PHP 8.4 + Postgres 16 (sub-issue 1.1)

**Files:**
- Modify: `laradock/.env` (criar do `.env.example` se não existir)

- [ ] **Step 2.1: Mover issue PAF 1.1 para In Progress**

Via MCP: update status da sub-issue `[1.1]` para `In Progress`.

- [ ] **Step 2.2: Verificar se `laradock/.env` existe**

Run: `ls -la laradock/.env`

Se não existir, criar copiando do template: `cp laradock/.env.example laradock/.env`

- [ ] **Step 2.3: Editar variáveis de versão**

Abrir `laradock/.env` e alterar/garantir os valores:

```
PHP_VERSION=8.4
WORKSPACE_PHP_VERSION=8.4
PHP_FPM_PHP_VERSION=8.4

POSTGRES_VERSION=16
POSTGRES_DB=portalartfinal
POSTGRES_USER=portalartfinal
POSTGRES_PASSWORD=secret

WORKSPACE_TIMEZONE=America/Sao_Paulo
WORKSPACE_INSTALL_NODE=true
WORKSPACE_INSTALL_YARN=true
WORKSPACE_INSTALL_NPM_GULP=false
WORKSPACE_INSTALL_NPM_BOWER=false

PHP_FPM_INSTALL_BCMATH=true
PHP_FPM_INSTALL_INTL=true
```

> **Nota:** `PHP_FPM_INSTALL_PGSQL`, `PHP_FPM_INSTALL_PG_CLIENT`,
> `PHP_FPM_INSTALL_PHPREDIS`, `WORKSPACE_INSTALL_PG_CLIENT` e
> `WORKSPACE_INSTALL_PHPREDIS` já vêm `=true` no `.env.example` padrão
> do Laradock (linhas 262, 268, 238, 178, 133). Não precisam ser
> repetidos na seção de overrides. **Não usar** `PHP_FPM_INSTALL_REDIS`,
> `PHP_FPM_INSTALL_PDO_PGSQL`, `PHP_FPM_INSTALL_GD`,
> `PHP_FPM_INSTALL_ZIP_ARCHIVE`, `WORKSPACE_INSTALL_PGSQL_CLIENT` ou
> `WORKSPACE_INSTALL_REDIS` — são variáveis fantasmas (não mapeadas
> em `docker-compose.yml`).

- [ ] **Step 2.4: Validar sintaxe do .env**

Run: `grep -c '^PHP_VERSION=8.4' laradock/.env`
Expected: `1`

Run: `grep -c '^POSTGRES_VERSION=16' laradock/.env`
Expected: `1`

- [ ] **Step 2.5: Commit (ainda não, git init é Task 6)**

Sub-issue 1.1 move para `Done` no Linear via MCP. Arquivo fica modificado no filesystem, commit acontece depois da Task 6.

---

## Task 3: Corrigir `composer.json` para PHP ^8.4 (sub-issue 1.2)

**Files:**
- Modify: `composer.json` linha 9

- [ ] **Step 3.1: Mover sub-issue 1.2 para In Progress**

Via MCP.

- [ ] **Step 3.2: Editar composer.json**

Abrir `composer.json` e alterar:

```diff
  "require": {
-     "php": "^8.2",
+     "php": "^8.4",
      "laravel/framework": "^13.0",
```

- [ ] **Step 3.3: Validar JSON**

Run: `php -r "json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR); echo 'OK';"`

Expected: `OK`

- [ ] **Step 3.4: Validar requisito PHP**

Run: `grep '"php":' composer.json`
Expected: `"php": "^8.4",`

- [ ] **Step 3.5: Marcar sub-issue como Done no Linear**

Via MCP.

---

## Task 4: Rodar `./docker-setup.sh` e validar boot (sub-issue 1.3)

**Files:** nenhum (execução de scripts)

- [ ] **Step 4.1: Mover sub-issue 1.3 para In Progress**

- [ ] **Step 4.2: Executar setup script**

Run: `./docker-setup.sh`

Expected output: 6 passos com `[1/6]` até `[6/6]` concluídos, mensagem final "Setup concluído com sucesso!" e lista de URLs.

Se falhar em algum passo, diagnosticar antes de continuar (não passar para Step 4.3).

- [ ] **Step 4.3: Validar que containers estão up**

Run: `make status`

Expected: 8 containers listados com status `Up` — `workspace`, `php-fpm`, `nginx`, `postgres`, `redis`, `laravel-horizon`, `pgadmin`, `mailpit`.

- [ ] **Step 4.4: Validar PHP 8.4 no workspace**

Run: `make bash -c 'php -v | head -1'`

Ou entrar no bash e rodar `php -v`:

```bash
make bash
# dentro do container:
php -v
# esperar: PHP 8.4.x (cli)
exit
```

Expected: primeira linha começa com `PHP 8.4.`

- [ ] **Step 4.5: Validar Postgres acessível**

Run via make bash:
```bash
psql -h postgres -U portalartfinal -d portalartfinal -c '\l'
# senha: secret
```

Expected: lista de databases, incluindo `portalartfinal`.

- [ ] **Step 4.6: Validar Redis acessível**

Run via make bash:
```bash
redis-cli -h redis ping
```

Expected: `PONG`

- [ ] **Step 4.7: Marcar sub-issue 1.3 como Done no Linear**

---

## Task 5: Validar URLs + criar `docs/INFRA.md` (sub-issue 1.4)

**Files:**
- Create: `docs/INFRA.md`

- [ ] **Step 5.1: Mover sub-issue 1.4 para In Progress**

- [ ] **Step 5.2: Smoke test das 5 URLs (via curl)**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/horizon
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/pulse
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:5050
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8125
```

Expected: todos retornam `200` (ou `302` redirect para app se middleware exigir login). Documentar qualquer caso que não seja 200/302 na seção de troubleshooting do INFRA.md.

- [ ] **Step 5.3: Criar `docs/INFRA.md`**

Conteúdo:

```markdown
# Infraestrutura Local — Portal ArtFinal

**Stack:** Docker (Laradock) · PHP 8.4 · PostgreSQL 16 · Redis · Nginx · Horizon · Pulse · pgAdmin · Mailpit

---

## URLs e Serviços

| Serviço | URL | Porta | Container |
|---------|-----|-------|-----------|
| Aplicação Laravel | http://localhost | 80 | nginx + php-fpm |
| Laravel Horizon | http://localhost/horizon | 80 | laravel-horizon |
| Laravel Pulse | http://localhost/pulse | 80 | php-fpm |
| pgAdmin | http://localhost:5050 | 5050 | pgadmin |
| Mailpit | http://localhost:8125 | 8125 | mailpit |
| PostgreSQL | postgres:5432 (interno) | 5432 | postgres |
| Redis | redis:6379 (interno) | 6379 | redis |

---

## Comandos Makefile

| Comando | Descrição |
|---------|-----------|
| `make up` | Sobe todos os containers |
| `make down` | Para todos os containers |
| `make restart` | Reinicia os containers |
| `make build` | Rebuilda os containers |
| `make bash` | Entra no workspace |
| `make artisan <cmd>` | Roda `php artisan <cmd>` no workspace |
| `make composer <cmd>` | Roda `composer <cmd>` no workspace |
| `make npm <cmd>` | Roda `npm <cmd>` no workspace |
| `make migrate` | `php artisan migrate` |
| `make fresh` | `php artisan migrate:fresh --seed` |
| `make seed` | `php artisan db:seed` |
| `make test` | `php artisan test` |
| `make logs` | `docker compose logs -f --tail=100` |
| `make status` | `docker compose ps` |
| `make setup` | Roda `docker-setup.sh` do zero |

---

## Primeiro Boot

```bash
# Na raiz do projeto
./docker-setup.sh
```

O script faz em 6 passos:
1. Sobe containers (workspace, php-fpm, nginx, postgres, redis, horizon, pgadmin, mailpit)
2. Aguarda Postgres ficar pronto
3. Instala dependências PHP (`composer install`)
4. Gera APP_KEY, roda migrations, instala Horizon, publica Pulse
5. Instala dependências Node (`npm install && npm run build`)
6. Reinicia Horizon

---

## Acessos

**PostgreSQL (pgAdmin ou psql):**
- Host: `postgres` (interno) ou `localhost` (externo)
- Porta: `5432`
- Database: `portalartfinal`
- User: `portalartfinal`
- Password: `secret`

**Redis:**
- Host: `redis` (interno) ou `localhost` (externo)
- Porta: `6379`
- Sem password (dev)

**Mailpit:**
- SMTP: `mailpit:1025`
- UI: `http://localhost:8125`

---

## Troubleshooting

### Containers não sobem
```bash
make down
make build
make up
make logs
```

### Permissão em storage/
Dentro do workspace:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Postgres recusa conexão
Verificar se o container subiu antes do php-fpm:
```bash
make status
# Se postgres estiver reiniciando:
docker compose logs postgres
```

### Horizon não processa jobs
Após alterar `config/horizon.php`:
```bash
make horizon
```

### Resetar banco e seeds
```bash
make fresh
```

---

## Referências

- `docs/01-ARCHITECTURE-GUIDE.md` — estrutura de pastas do projeto
- `docs/02-CONVENTIONS.md §9` — padrões de cache Redis
- `docs/02-CONVENTIONS.md §10` — filas do Horizon
- `CLAUDE.md §20` — resumo rápido do ambiente
```

- [ ] **Step 5.4: Marcar sub-issue 1.4 e issue-pai #1 como Done**

Via MCP: `[1.4]` → Done. Issue-pai #1 → Done (quando todas sub-issues estão Done).

---

## Task 6: `git init` + primeiro commit + branch `develop` (sub-issue 2.1)

**Files:**
- Modify: `.gitignore`

- [ ] **Step 6.1: Mover sub-issue 2.1 para In Progress**

- [ ] **Step 6.2: Verificar se git já foi inicializado**

Run: `git status 2>&1 | head -1`

Expected (antes): `fatal: not a git repository`

Se já for um repo, pular para Step 6.4.

- [ ] **Step 6.3: Inicializar repositório git**

Run: `git init`

Expected: `Initialized empty Git repository in .../portalartfinal_v2/.git/`

Configurar branch default como `main`:
```bash
git symbolic-ref HEAD refs/heads/main
```

- [ ] **Step 6.4: Expandir `.gitignore`**

Ler o `.gitignore` atual e ADICIONAR (sem remover existentes) as seguintes linhas se não estiverem presentes:

```
# Husky
/.husky/_

# Ferramentas de qualidade
.phpunit.result.cache
.phpstan.cache
coverage/

# IDEs
.idea/
.vscode/*
!.vscode/settings.json
!.vscode/extensions.json

# Sistema
.DS_Store
Thumbs.db

# Docker local
laradock/.env
```

- [ ] **Step 6.5: Primeiro commit**

```bash
git add .
git commit -m "chore(infra): inicializa repositório com Laravel 13 skeleton + Laradock + docs"
```

Expected: commit criado com todos os arquivos atuais do projeto.

**Nota:** este commit **ainda não passa por Husky** (não existe). Husky será instalado na Task 13.

- [ ] **Step 6.6: Criar branch `develop`**

```bash
git checkout -b develop
```

Expected: `Switched to a new branch 'develop'`

- [ ] **Step 6.7: Marcar sub-issue 2.1 como Done no Linear**

---

## Task 7: Criar `commitlint.config.js` (sub-issue 2.2)

**Files:**
- Create: `commitlint.config.js`

- [ ] **Step 7.1: Mover sub-issue 2.2 para In Progress**

- [ ] **Step 7.2: Criar `commitlint.config.js` na raiz do projeto**

Conteúdo exato (extends + rules alinhados com `docs/02-CONVENTIONS.md §1.2-§1.3`):

```javascript
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'refactor',
                'docs',
                'style',
                'test',
                'chore',
                'perf',
                'ci',
                'revert',
            ],
        ],
        'scope-enum': [
            2,
            'always',
            [
                'admin',
                'portal',
                'gateway',
                'financeiro',
                'adesao',
                'auth',
                'infra',
                'models',
                'docs',
                'ui',
            ],
        ],
        'scope-empty': [2, 'never'],
        'subject-case': [0],
        'subject-max-length': [2, 'always', 72],
        'subject-full-stop': [2, 'never', '.'],
        'header-max-length': [2, 'always', 72],
    },
};
```

- [ ] **Step 7.3: Validar sintaxe JS**

Run: `node -e "require('./commitlint.config.js'); console.log('OK')"`

Expected: `OK`

**Nota:** este teste pode falhar com "Cannot find module '@commitlint/config-conventional'" — tudo bem, o pacote será instalado na Task 13. O importante aqui é validar a sintaxe do arquivo JS em si, que é: `node --check commitlint.config.js`.

Run: `node --check commitlint.config.js`
Expected: sem output (sintaxe OK)

- [ ] **Step 7.4: Marcar sub-issue 2.2 como Done no Linear**

---

## Task 8: Revisar `.editorconfig` e `.gitattributes` (sub-issue 2.3)

**Files:**
- Modify: `.editorconfig`
- Modify: `.gitattributes`

- [ ] **Step 8.1: Mover sub-issue 2.3 para In Progress**

- [ ] **Step 8.2: Sobrescrever `.editorconfig` com conteúdo padronizado**

Conteúdo exato (do `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2.1`):

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{js,ts,jsx,tsx,vue,css,scss,json,yaml,yml}]
indent_size = 2

[*.blade.php]
indent_size = 4

[Makefile]
indent_style = tab
```

- [ ] **Step 8.3: Verificar/atualizar `.gitattributes`**

Ler o arquivo atual e garantir que contém:

```
* text=auto eol=lf

*.blade.php text eol=lf
*.php text eol=lf
*.js text eol=lf
*.json text eol=lf
*.md text eol=lf
*.yml text eol=lf
*.sh text eol=lf

*.png binary
*.jpg binary
*.gif binary
*.ico binary
*.woff binary
*.woff2 binary
```

Se já existir conteúdo compatível, manter. Senão, sobrescrever.

- [ ] **Step 8.4: Commit das mudanças do bloco #2**

Agora com git inicializado, podemos commitar tudo que foi mudado nas Tasks 2, 3, 6, 7 e 8.

Run:
```bash
git add composer.json laradock/.env .gitignore commitlint.config.js .editorconfig .gitattributes
git commit -m "chore(infra): padroniza editorconfig, gitattributes e commitlint"
```

**Nota:** pre-commit hook ainda não existe (é Task 13), então o commit passa sem validação.

- [ ] **Step 8.5: Marcar sub-issue 2.3 e issue-pai #2 como Done**

---

## Task 9: Criar `pint.json` (sub-issue 3.1)

**Files:**
- Create: `pint.json`

- [ ] **Step 9.1: Mover sub-issue 3.1 para In Progress**

- [ ] **Step 9.2: Criar `pint.json` na raiz**

Conteúdo exato (do `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2.2`):

```json
{
    "preset": "laravel",
    "rules": {
        "align_multiline_comment": {
            "comment_type": "phpdocs_only"
        },
        "array_indentation": true,
        "array_syntax": {
            "syntax": "short"
        },
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        },
        "combine_consecutive_issets": true,
        "combine_consecutive_unsets": true,
        "concat_space": {
            "spacing": "one"
        },
        "declare_strict_types": true,
        "fully_qualified_strict_types": true,
        "global_namespace_import": {
            "import_classes": true,
            "import_constants": true,
            "import_functions": true
        },
        "method_argument_space": {
            "on_multiline": "ensure_fully_multiline"
        },
        "no_empty_comment": true,
        "no_unused_imports": true,
        "not_operator_with_space": false,
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "method_public",
                "method_protected",
                "method_private"
            ]
        },
        "ordered_imports": {
            "sort_algorithm": "alpha",
            "imports_order": ["const", "class", "function"]
        },
        "single_line_empty_body": true,
        "trailing_comma_in_multiline": {
            "elements": ["arguments", "arrays", "match", "parameters"]
        },
        "yoda_style": false
    },
    "exclude": [
        "node_modules",
        "vendor",
        "storage",
        "bootstrap/cache"
    ]
}
```

- [ ] **Step 9.3: Validar JSON**

Run: `php -r "json_decode(file_get_contents('pint.json'), true, 512, JSON_THROW_ON_ERROR); echo 'OK';"`
Expected: `OK`

- [ ] **Step 9.4: Rodar Pint para testar**

Run: `make bash -c "./vendor/bin/pint --test"`

Expected: zero ou poucas mudanças (o projeto é praticamente vazio). Se reportar arquivos a mudar, aplicar com `make bash -c "./vendor/bin/pint"` e documentar.

- [ ] **Step 9.5: Marcar sub-issue 3.1 como Done no Linear**

---

## Task 10: Criar `.prettierrc` + `.prettierignore` + instalar plugin Blade (sub-issue 3.2)

**Files:**
- Create: `.prettierrc`
- Create: `.prettierignore`
- Modify: `package.json` (devDependencies)

- [ ] **Step 10.1: Mover sub-issue 3.2 para In Progress**

- [ ] **Step 10.2: Criar `.prettierrc`**

Conteúdo exato (do `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2.3`):

```json
{
    "printWidth": 120,
    "semi": true,
    "singleQuote": true,
    "tabWidth": 4,
    "trailingComma": "all",
    "useTabs": false,
    "endOfLine": "lf",
    "bracketSameLine": false,
    "htmlWhitespaceSensitivity": "css",
    "plugins": [
        "prettier-plugin-blade",
        "prettier-plugin-tailwindcss"
    ],
    "overrides": [
        {
            "files": ["*.blade.php"],
            "options": {
                "parser": "blade",
                "tabWidth": 4,
                "bladePhpFormatting": "safe",
                "bladePhpFormattingTargets": ["directiveArgs", "echo"],
                "bladeDirectiveArgSpacing": "space",
                "bladeEchoSpacing": "space",
                "bladeComponentPrefixes": ["x", "livewire"]
            }
        },
        {
            "files": ["*.{js,ts,jsx,tsx,vue,css,scss}"],
            "options": {
                "tabWidth": 2,
                "singleQuote": true
            }
        },
        {
            "files": ["*.json"],
            "options": {
                "tabWidth": 2
            }
        }
    ]
}
```

- [ ] **Step 10.3: Criar `.prettierignore`**

Conteúdo exato (do `§2.4`):

```
vendor/
node_modules/
storage/
bootstrap/cache/
public/build/
public/vendor/
*.min.js
*.min.css
laradock/
```

- [ ] **Step 10.4: Instalar plugin prettier-plugin-blade**

Run: `make npm install --save-dev prettier-plugin-blade`

Expected: pacote adicionado ao `devDependencies` do `package.json`. `prettier-plugin-tailwindcss` já está instalado (confirmei no package.json).

- [ ] **Step 10.5: Validar que prettier consegue parsear**

Run: `make bash -c "npx prettier --check .prettierrc"`

Expected: `All matched files use Prettier code style!` ou similar.

- [ ] **Step 10.6: Marcar sub-issue 3.2 como Done no Linear**

---

## Task 11: Instalar PHPStan (Larastan) level 6 (sub-issue 3.3)

**Files:**
- Create: `phpstan.neon`
- Modify: `composer.json` (devDependencies)

- [ ] **Step 11.1: Mover sub-issue 3.3 para In Progress**

- [ ] **Step 11.2: Instalar Larastan via Composer**

Run: `make composer require --dev larastan/larastan`

Expected: `larastan/larastan` adicionado a `require-dev` do `composer.json`.

- [ ] **Step 11.3: Criar `phpstan.neon`**

Conteúdo exato (do `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2.5`):

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 6
    paths:
        - app/
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
    treatPhpDocTypesAsCertain: false
```

- [ ] **Step 11.4: Rodar PHPStan para validar**

Run: `make bash -c "./vendor/bin/phpstan analyse --memory-limit=512M"`

Expected: `[OK] No errors` (o projeto só tem `app/Models/User.php` e `app/Providers/AppServiceProvider.php`, ambos limpos).

Se houver erros, corrigi-los inline antes de prosseguir.

- [ ] **Step 11.5: Marcar sub-issue 3.3 como Done no Linear**

---

## Task 12: Instalar ESLint + criar `.eslintrc.json` (sub-issue 3.4)

**Files:**
- Create: `.eslintrc.json`
- Modify: `package.json` (devDependencies)

- [ ] **Step 12.1: Mover sub-issue 3.4 para In Progress**

- [ ] **Step 12.2: Instalar ESLint + config prettier**

Run: `make npm install --save-dev eslint eslint-config-prettier`

Expected: ambos adicionados ao `devDependencies`.

- [ ] **Step 12.3: Criar `.eslintrc.json`**

Conteúdo exato (do `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2.6`):

```json
{
    "env": {
        "browser": true,
        "es2024": true,
        "node": true
    },
    "extends": [
        "eslint:recommended",
        "prettier"
    ],
    "parserOptions": {
        "ecmaVersion": "latest",
        "sourceType": "module"
    },
    "rules": {
        "no-console": "warn",
        "no-unused-vars": "warn",
        "prefer-const": "error",
        "no-var": "error"
    }
}
```

- [ ] **Step 12.4: Rodar ESLint na pasta resources/js**

Run: `make bash -c "npx eslint resources/js/ --no-error-on-unmatched-pattern"`

Expected: zero erros (resources/js está praticamente vazio no skeleton).

- [ ] **Step 12.5: Marcar sub-issue 3.4 como Done no Linear**

---

## Task 13: Instalar Husky + lint-staged + ativar hooks (sub-issue 3.5)

**Files:**
- Create: `.husky/pre-commit`
- Create: `.husky/commit-msg`
- Modify: `package.json` (devDependencies + lint-staged + scripts)

- [ ] **Step 13.1: Mover sub-issue 3.5 para In Progress**

- [ ] **Step 13.2: Instalar Husky, lint-staged, commitlint**

Run:
```bash
make npm install --save-dev husky lint-staged @commitlint/cli @commitlint/config-conventional
```

Expected: 4 pacotes adicionados a `devDependencies`.

- [ ] **Step 13.3: Inicializar Husky**

Run: `make bash -c "npx husky init"`

Expected:
- Cria `.husky/` com `pre-commit` (contendo `npm test` por padrão)
- Adiciona `"prepare": "husky"` a `scripts` do `package.json`

- [ ] **Step 13.4: Sobrescrever `.husky/pre-commit`**

Conteúdo:

```sh
npx lint-staged
```

Dar permissão de execução: `chmod +x .husky/pre-commit`

- [ ] **Step 13.5: Criar `.husky/commit-msg`**

Conteúdo:

```sh
npx --no-install commitlint --edit $1
```

Dar permissão de execução: `chmod +x .husky/commit-msg`

- [ ] **Step 13.6: Adicionar bloco `lint-staged` ao `package.json`**

Abrir `package.json` e adicionar (dentro do objeto raiz, após `devDependencies`):

```json
"lint-staged": {
    "*.php": [
        "./vendor/bin/pint --dirty"
    ],
    "*.blade.php": [
        "prettier --write"
    ],
    "*.{js,ts,jsx,tsx,vue}": [
        "eslint --fix",
        "prettier --write"
    ],
    "*.{css,scss}": [
        "prettier --write"
    ],
    "*.{json,md,yaml,yml}": [
        "prettier --write"
    ]
}
```

- [ ] **Step 13.7: Adicionar scripts npm ao `package.json`**

Na seção `scripts`, adicionar (manter `dev` e `build` existentes):

```json
"scripts": {
    "dev": "vite",
    "build": "vite build",
    "format": "prettier --write 'resources/**/*.{blade.php,js,css,json}'",
    "format:check": "prettier --check 'resources/**/*.{blade.php,js,css,json}'",
    "lint:php": "./vendor/bin/pint",
    "lint:php:check": "./vendor/bin/pint --test",
    "lint:js": "eslint resources/js/ --fix",
    "lint:js:check": "eslint resources/js/",
    "analyse": "./vendor/bin/phpstan analyse",
    "test": "php artisan test",
    "quality": "npm run lint:php:check && npm run format:check && npm run analyse && npm run test",
    "prepare": "husky"
}
```

- [ ] **Step 13.8: Testar hook pre-commit (commit válido)**

Criar um commit de teste:
```bash
git add pint.json .prettierrc .prettierignore phpstan.neon .eslintrc.json .husky package.json composer.json commitlint.config.js
git commit -m "chore(infra): configura pint, prettier, phpstan, eslint, husky e commitlint"
```

Expected: husky roda lint-staged (Pint --dirty + Prettier), commitlint valida a mensagem, commit é criado.

- [ ] **Step 13.9: Testar hook commit-msg (commit inválido)**

Criar um arquivo dummy e tentar commitar com mensagem fora do padrão:
```bash
echo "# test" > /tmp/test.md
cp /tmp/test.md test.md
git add test.md
git commit -m "mensagem fora do padrao" 2>&1 | tail -5
```

Expected: commit **rejeitado** pelo commitlint. Mensagem tipo "subject may not be empty" ou "type must be one of [...]".

Limpar: `git restore --staged test.md && rm test.md`

- [ ] **Step 13.10: Marcar sub-issue 3.5 e issue-pai #3 como Done**

---

## Task 14: Instalar pacotes Composer essenciais (sub-issue 4.1)

**Files:**
- Modify: `composer.json` (require)

- [ ] **Step 14.1: Mover sub-issue 4.1 para In Progress**

- [ ] **Step 14.2: Instalar 7 pacotes essenciais**

Run:
```bash
make composer require \
  livewire/livewire \
  spatie/laravel-permission \
  spatie/laravel-activitylog \
  barryvdh/laravel-dompdf \
  maatwebsite/excel \
  laravellegends/pt-br-validator \
  guzzlehttp/guzzle
```

Expected: 7 pacotes adicionados a `require`, Composer rodou com sucesso.

- [ ] **Step 14.3: Publicar vendor dos pacotes Spatie**

Run:
```bash
make artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"
make artisan vendor:publish --provider="Spatie\\Activitylog\\ActivitylogServiceProvider" --tag="activitylog-config"
make artisan vendor:publish --provider="Spatie\\Activitylog\\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

Expected: cria `config/permission.php`, `config/activitylog.php`, e migrations em `database/migrations/`.

**Nota:** NÃO rodar `make migrate` ainda — as migrations serão validadas na Sprint 2 quando o modelo de dados do projeto começar.

- [ ] **Step 14.4: Validar que pacotes estão instalados**

Run: `make bash -c "composer show | grep -E '(livewire|spatie|dompdf|excel|pt-br-validator|guzzle)'"`

Expected: 7 linhas, uma por pacote.

- [ ] **Step 14.5: Marcar sub-issue 4.1 como Done no Linear**

---

## Task 15: Instalar pacotes Composer dev-only (sub-issue 4.2)

**Files:**
- Modify: `composer.json` (require-dev)

- [ ] **Step 15.1: Mover sub-issue 4.2 para In Progress**

- [ ] **Step 15.2: Instalar Debugbar + Pest**

Run:
```bash
make composer require --dev \
  barryvdh/laravel-debugbar \
  pestphp/pest \
  pestphp/pest-plugin-laravel
```

**Nota:** Telescope foi postergado (instalar na Sprint 2).

Expected: 3 pacotes adicionados a `require-dev`.

- [ ] **Step 15.3: Validar que pacotes estão instalados**

Run: `make bash -c "composer show --dev | grep -E '(debugbar|pest)'"`

Expected: linhas para `barryvdh/laravel-debugbar`, `pestphp/pest`, `pestphp/pest-plugin-laravel`.

- [ ] **Step 15.4: Marcar sub-issue 4.2 como Done no Linear**

---

## Task 16: Auditar pacotes NPM do Inspinia (sub-issue 4.3)

**Files:** nenhum (auditoria read-only + instalação condicional)

- [ ] **Step 16.1: Mover sub-issue 4.3 para In Progress**

- [ ] **Step 16.2: Listar pacotes instalados**

Run: `make bash -c "npm ls --depth=0"` ou ler diretamente o `package.json`.

- [ ] **Step 16.3: Verificar cada pacote de `03-TOOLS-AND-PACKAGES.md §2.2`**

Checklist dos 8 pacotes esperados do §2.2:

- [ ] `apexcharts` (já instalado)
- [ ] `flatpickr` (já instalado)
- [ ] `choices.js` (já instalado)
- [ ] `inputmask` (já instalado)
- [ ] `sortablejs` (já instalado)
- [ ] `dropzone` (já instalado)
- [ ] `sweetalert2` (já instalado)
- [ ] `tinymce` (**não** instalado por padrão no template)

Para cada pacote faltante, instalar via `make npm install <pacote>`.

**Nota:** confirmei ao ler o package.json na Fase de exploração — os 7 primeiros já estão. Só `tinymce` pode faltar. Se estiver, pular instalação.

- [ ] **Step 16.4: Rodar `npm run build` para validar**

Run: `make npm run build`

Expected: build completa sem erros.

- [ ] **Step 16.5: Marcar sub-issue 4.3 como Done no Linear**

---

## Task 17: Criar árvore de pastas em `app/` (sub-issue 4.4)

**Files:**
- Create: 14 pastas em `app/` com `.gitkeep`

- [ ] **Step 17.1: Mover sub-issue 4.4 para In Progress**

- [ ] **Step 17.2: Criar as pastas do `01-ARCHITECTURE-GUIDE.md`**

Run (no host, fora do container):
```bash
mkdir -p \
  app/Actions \
  app/DTOs \
  app/Enums \
  app/Services \
  app/Policies \
  app/Observers \
  app/Jobs \
  app/Events \
  app/Listeners \
  app/Traits \
  app/Exceptions \
  app/Http/Middleware \
  app/Http/Requests \
  app/Console/Commands \
  app/Livewire/Admin \
  app/Livewire/Portal
```

- [ ] **Step 17.3: Criar `.gitkeep` em cada uma**

Run:
```bash
touch \
  app/Actions/.gitkeep \
  app/DTOs/.gitkeep \
  app/Enums/.gitkeep \
  app/Services/.gitkeep \
  app/Policies/.gitkeep \
  app/Observers/.gitkeep \
  app/Jobs/.gitkeep \
  app/Events/.gitkeep \
  app/Listeners/.gitkeep \
  app/Traits/.gitkeep \
  app/Exceptions/.gitkeep \
  app/Http/Middleware/.gitkeep \
  app/Http/Requests/.gitkeep \
  app/Console/Commands/.gitkeep \
  app/Livewire/Admin/.gitkeep \
  app/Livewire/Portal/.gitkeep
```

- [ ] **Step 17.4: Validar estrutura criada**

Run: `find app -type d -maxdepth 2 | sort`

Expected: lista incluindo todas as pastas novas, em ordem alfabética.

- [ ] **Step 17.5: Marcar sub-issue 4.4 como Done no Linear**

---

## Task 18: `preventLazyLoading` + pt_BR localization (sub-issue 4.5)

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `composer.json` (require-dev)

- [ ] **Step 18.1: Mover sub-issue 4.5 para In Progress**

- [ ] **Step 18.2: Instalar pt_BR localization**

Run:
```bash
make composer require --dev lucascudo/laravel-pt-br-localization
make artisan vendor:publish --tag=laravel-pt-br-localization
```

Expected: traduções copiadas para `lang/pt_BR/`.

- [ ] **Step 18.3: Editar `AppServiceProvider::boot()`**

Abrir `app/Providers/AppServiceProvider.php`. O método `boot()` atual é provavelmente vazio. Substituir por:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }
}
```

- [ ] **Step 18.4: Validar que APP_LOCALE=pt_BR no .env.example**

Run: `grep 'APP_LOCALE' .env.example`

Expected: `APP_LOCALE=pt_BR` (já confirmado na exploração, mas revalidar).

- [ ] **Step 18.5: Smoke test do preventLazyLoading**

Run: `make artisan tinker`

Dentro do tinker:
```php
// Testar que lazy loading bloqueia:
>>> \App\Models\User::factory()->create();
>>> \App\Models\User::all()->first()->someNonExistentRelation ?? 'no relation';
```

Expected: pode lançar exception relacionada a lazy loading ou funcionar dependendo de Model ter relações. O importante é que `Model::preventLazyLoading` está registrado.

Verificação alternativa mais direta via tinker:
```php
>>> \Illuminate\Database\Eloquent\Model::preventsLazyLoading();
=> true
```

Expected: `true` em ambiente não-production.

- [ ] **Step 18.6: Marcar sub-issue 4.5 como Done no Linear**

---

## Task 19: Configurar Pest + estrutura tests + smoke test (sub-issue 4.6)

**Files:**
- Modify: `phpunit.xml`
- Create: `tests/Feature/{Admin,Portal,Webhook}/.gitkeep`
- Create: `tests/Unit/{Services,Models}/.gitkeep`
- Create: `tests/Feature/EnvironmentTest.php`
- Create: `tests/Pest.php` (se pest:install não criar)

- [ ] **Step 19.1: Mover sub-issue 4.6 para In Progress**

- [ ] **Step 19.2: Inicializar Pest**

Run: `make bash -c "./vendor/bin/pest --init"` ou `make artisan pest:install`

Expected: cria `tests/Pest.php` se ainda não existir. Pode pular se Pest já for o runner padrão (pestphp/pest-plugin-laravel instalado na Task 15 deveria configurar automaticamente).

Verificar: `ls tests/Pest.php`

Se não existir, criar manualmente:

```php
<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function something()
{
    // ...
}
```

- [ ] **Step 19.3: Criar pastas de teste**

Run (no host):
```bash
mkdir -p \
  tests/Feature/Admin \
  tests/Feature/Portal \
  tests/Feature/Webhook \
  tests/Unit/Services \
  tests/Unit/Models

touch \
  tests/Feature/Admin/.gitkeep \
  tests/Feature/Portal/.gitkeep \
  tests/Feature/Webhook/.gitkeep \
  tests/Unit/Services/.gitkeep \
  tests/Unit/Models/.gitkeep
```

- [ ] **Step 19.4: Criar `tests/Feature/EnvironmentTest.php`**

Conteúdo:

```php
<?php

declare(strict_types=1);

it('boots the application in testing environment', function () {
    expect(app()->environment('testing'))->toBeTrue();
});

it('loads the pt_BR locale', function () {
    expect(config('app.locale'))->toBe('pt_BR');
});

it('uses redis as cache store in non-testing envs (smoke check config)', function () {
    // Em testing o cache default pode ser array, então só checamos que o driver redis está definido.
    expect(config('cache.stores.redis'))->not()->toBeNull();
});
```

- [ ] **Step 19.5: Rodar o teste**

Run: `make test`

Expected: 3 tests passed, ou ao menos o `boots the application` passa. Se o pt_BR locale test falhar, significa que algum pacote ainda não está publicado corretamente — investigar antes de prosseguir.

- [ ] **Step 19.6: Commit das mudanças do bloco #4**

Agora fica um commit grande das mudanças das Tasks 14-19:

Run:
```bash
git add composer.json composer.lock package.json package-lock.json \
  app/Providers/AppServiceProvider.php \
  app/Actions app/DTOs app/Enums app/Services app/Policies \
  app/Observers app/Jobs app/Events app/Listeners app/Traits \
  app/Exceptions app/Http/Middleware app/Http/Requests \
  app/Console/Commands app/Livewire \
  config/permission.php config/activitylog.php \
  database/migrations \
  lang/pt_BR \
  tests/
git commit -m "feat(infra): instala pacotes essenciais, cria estrutura de pastas e setup Pest"
```

Expected: Husky roda pre-commit (Pint --dirty + Prettier), commit-msg valida (escopo `infra`, tipo `feat`). Commit criado.

- [ ] **Step 19.7: Marcar sub-issue 4.6 e issue-pai #4 como Done**

---

## Task 20: Configurar `config/horizon.php` com as 6 filas (sub-issue 5.1)

**Files:**
- Modify: `config/horizon.php`

- [ ] **Step 20.1: Mover sub-issue 5.1 para In Progress**

- [ ] **Step 20.2: Publicar config do Horizon (se ainda não estiver)**

Run: `ls config/horizon.php`

Se não existir: `make artisan horizon:install`

Expected: `config/horizon.php` criado pelo Horizon package.

- [ ] **Step 20.3: Editar bloco `environments`**

Abrir `config/horizon.php` e substituir o bloco `environments` por:

```php
'environments' => [
    'production' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default', 'emails', 'exports', 'pdf'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-high-priority' => [
            'connection' => 'redis',
            'queue' => ['gateway', 'webhooks'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'size',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'backoff' => [10, 60, 300],
            'timeout' => 90,
            'nice' => 0,
        ],
    ],

    'local' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default', 'emails', 'exports', 'pdf'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 3,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
        ],
        'supervisor-high-priority' => [
            'connection' => 'redis',
            'queue' => ['gateway', 'webhooks'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'size',
            'maxProcesses' => 3,
            'memory' => 128,
            'tries' => 3,
            'backoff' => [10, 60, 300],
            'timeout' => 90,
        ],
    ],
],
```

- [ ] **Step 20.4: Restart Horizon**

Run: `make horizon`

Expected: container Horizon reinicia sem erro.

- [ ] **Step 20.5: Validar no navegador**

Abrir `http://localhost/horizon` e confirmar que a aba "Supervisors" lista:
- `supervisor-default` processando `default, emails, exports, pdf`
- `supervisor-high-priority` processando `gateway, webhooks`

Total: 6 filas distintas.

- [ ] **Step 20.6: Marcar sub-issue 5.1 como Done no Linear**

---

## Task 21: Configurar `config/logging.php` com canais custom (sub-issue 5.2)

**Files:**
- Modify: `config/logging.php`

- [ ] **Step 21.1: Mover sub-issue 5.2 para In Progress**

- [ ] **Step 21.2: Adicionar canais custom em `config/logging.php`**

Abrir `config/logging.php` e adicionar dentro do array `channels` (mantendo os canais existentes):

```php
'gateway' => [
    'driver' => 'daily',
    'path' => storage_path('logs/gateway.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 30,
    'replace_placeholders' => true,
],

'webhook' => [
    'driver' => 'daily',
    'path' => storage_path('logs/webhook.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 30,
    'replace_placeholders' => true,
],

'audit' => [
    'driver' => 'daily',
    'path' => storage_path('logs/audit.log'),
    'level' => env('LOG_LEVEL', 'info'),
    'days' => 90,
    'replace_placeholders' => true,
],
```

- [ ] **Step 21.3: Smoke test dos canais**

Run: `make artisan tinker`

Dentro do tinker:
```php
>>> Log::channel('gateway')->info('smoke test gateway');
>>> Log::channel('webhook')->info('smoke test webhook');
>>> Log::channel('audit')->info('smoke test audit');
```

Depois, verificar que os arquivos foram criados:
```bash
make bash -c "ls -la storage/logs/ | grep -E '(gateway|webhook|audit)'"
```

Expected: 3 arquivos (`gateway-YYYY-MM-DD.log`, `webhook-*.log`, `audit-*.log`) com conteúdo das 3 mensagens.

- [ ] **Step 21.4: Marcar sub-issue 5.2 como Done no Linear**

---

## Task 22: Validar Redis + criar `docs/CACHE-PREFIXOS.md` (sub-issue 5.3)

**Files:**
- Create: `docs/CACHE-PREFIXOS.md`

- [ ] **Step 22.1: Mover sub-issue 5.3 para In Progress**

- [ ] **Step 22.2: Validar configuração de cache no .env.example**

Run: `grep -E '^CACHE_STORE|^REDIS_CLIENT|^REDIS_HOST' .env.example`

Expected:
```
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
```

(Já confirmado na exploração, revalidar.)

- [ ] **Step 22.3: Smoke test do Cache Redis**

Run: `make artisan tinker`

Dentro do tinker:
```php
>>> Cache::put('config:test', 'ok', 60);
>>> Cache::get('config:test');
=> "ok"
>>> Cache::forget('config:test');
>>> Cache::get('config:test');
=> null
```

Expected: sequência acima retorna exatamente os valores listados.

- [ ] **Step 22.4: Criar `docs/CACHE-PREFIXOS.md`**

Conteúdo:

```markdown
# Prefixos Padronizados de Cache — Portal ArtFinal

**Referência:** `docs/02-CONVENTIONS.md §9`
**Store:** Redis (`CACHE_STORE=redis` em todos os ambientes)

---

## Padrão

Todo uso de `Cache::remember()`, `Cache::put()`, `Cache::get()`, `Cache::forget()` deve seguir o prefixo adequado abaixo. Isso permite debugar o que está no Redis (`redis-cli KEYS "config:*"`), fazer invalidação em massa quando necessário, e evita colisões de chaves entre módulos.

---

## Tabela de prefixos

| Prefixo | Uso | TTL típico | Quando invalidar |
|---------|-----|------------|------------------|
| `config:` | Configurações globais, dias de vencimento, categorias padrão | 24h | Ao salvar em `ConfiguracaoController` |
| `acl:` | Permissões e roles de cada admin user | 1h | Ao editar perfil ou permissões |
| `programacao:` | Programação de valor vigente por produto | 1h | Ao criar/editar programação |
| `dashboard:` | KPIs e contagens do dashboard admin | 5-15min | Auto-expira (não invalidar manual) |
| `contrato:` | Dados enriquecidos de contrato (snapshots calculados) | 1h | Ao editar contrato, produto ou condição |

---

## Exemplos

```php
// ✅ CORRETO
$config = Cache::remember('config:global', 86400, fn () =>
    ConfiguracaoGlobal::all()->pluck('valor', 'chave')
);

$perms = Cache::remember("acl:user:{$userId}", 3600, fn () =>
    $user->getAllPermissions()->pluck('name')->toArray()
);

Cache::forget('config:global'); // ao salvar

// ❌ ERRADO
Cache::remember('global_config', 86400, fn () => ...); // sem prefixo
Cache::forever('dashboard:kpis', fn () => ...);         // sem TTL
```

---

## Regras

1. **SEMPRE** usar TTL explícito. `Cache::forever()` é proibido (§9.3).
2. **NUNCA** cachear dados que mudam via webhooks (parcelas, pagamentos) — §9.2.
3. **NUNCA** cachear drafts de adesão ou dados monetários em checkout — §9.2.
4. Invalidar com `Cache::forget()` sempre que o dado-fonte for alterado.
5. Chaves com ID de entidade usam dois-pontos como separador: `contrato:{id}`, `acl:user:{id}`.

---

## Debug no Redis (dev)

```bash
# Listar todas as chaves de cache do projeto
make bash
redis-cli -h redis KEYS "laravel_database_*"

# Chaves por prefixo
redis-cli -h redis KEYS "laravel_database_config:*"

# Valor de uma chave
redis-cli -h redis GET "laravel_database_config:global"
```

**Nota:** o prefixo `laravel_database_` vem do `CACHE_PREFIX` do Laravel (default). Ajustar se mudar via `.env`.
```

- [ ] **Step 22.5: Commit final do bloco #5**

```bash
git add config/horizon.php config/logging.php docs/CACHE-PREFIXOS.md
git commit -m "feat(infra): configura horizon com 6 filas, canais de log custom e docs cache"
```

Expected: Husky roda sem erros, commit criado.

- [ ] **Step 22.6: Marcar sub-issue 5.3 e issue-pai #5 como Done**

- [ ] **Step 22.7: Validação final consolidada do Cycle 1**

Rodar o checklist completo do `spec §7`:

```bash
# 1. Serviços up
make status
# Expected: 8 containers Up

# 2. PHP 8.4
make bash -c 'php -v | head -1'
# Expected: PHP 8.4.x

# 3. Quality gate completo
make bash -c "npm run quality"
# Expected: pint --test OK, prettier --check OK, phpstan OK, tests pass

# 4. Hook commit-msg recusa mensagem inválida
echo 'dummy' > /tmp/dummy.txt
cp /tmp/dummy.txt dummy.txt
git add dummy.txt
git commit -m "mensagem ruim" 2>&1 | tail -5
# Expected: ✖ subject may not be empty OR type must be one of [feat, ...]
git restore --staged dummy.txt && rm dummy.txt

# 5. Horizon lista 6 filas
# Abrir http://localhost/horizon no browser, aba Supervisors
# Expected: supervisor-default (default, emails, exports, pdf) + supervisor-high-priority (gateway, webhooks)

# 6. Log channels funcionam
make artisan tinker
>>> Log::channel('gateway')->info('final smoke');
>>> exit
make bash -c "ls storage/logs/gateway-*.log"
# Expected: arquivo existe

# 7. Pacotes essenciais instalados
make bash -c "composer show | grep -E '(livewire|spatie|dompdf|excel)'"
# Expected: 5+ linhas

# 8. Árvore de pastas
find app -type d -maxdepth 2 | sort
# Expected: 16+ pastas listadas

# 9. Pest roda
make test
# Expected: tests passed
```

Se TODOS passarem, o Cycle 1 está concluído. Marcar todas as 5 issues-pai como Done no Linear via MCP e fechar o Cycle.

---

## Critério de Conclusão do Plano

- [ ] Todas as 21 sub-issues no Linear estão `Done`
- [ ] Todas as 5 issues-pai no Linear estão `Done`
- [ ] Cycle 1 está `Completed` no Linear
- [ ] `make quality` passa 100%
- [ ] `git log` mostra os 5 commits esperados (ordem do mais antigo para o mais recente):
  1. `chore(infra): inicializa repositório com Laravel 13 skeleton + Laradock + docs` (Task 6.5)
  2. `chore(infra): padroniza editorconfig, gitattributes e commitlint` (Task 8.4)
  3. `chore(infra): configura pint, prettier, phpstan, eslint, husky e commitlint` (Task 13.8)
  4. `feat(infra): instala pacotes essenciais, cria estrutura de pastas e setup Pest` (Task 19.6)
  5. `feat(infra): configura horizon com 6 filas, canais de log custom e docs cache` (Task 22.5)

- [ ] O repositório está pronto para começar a Sprint 1 real do PRD (criação de migrations, models, guards, layouts)

---

## Referências

- Spec: `docs/superpowers/specs/2026-04-09-setup-inicial-padronizacao-design.md`
- Conventions fonte: `docs/02-CONVENTIONS.md`
- Configs literais: `docs/08-PADRONIZACAO-SPRINTS-AGENTES.md §2`
- Pacotes: `docs/03-TOOLS-AND-PACKAGES.md`
- Arquitetura: `docs/01-ARCHITECTURE-GUIDE.md`
- Linear: `docs/09-LINEAR-GUIDE.md`
- Contexto geral: `CLAUDE.md`
