---
titulo: Setup de Ambiente de Desenvolvimento
versao: 1.0.0
data: 2026-04-17
autores:
    - DevOps Engineering
escopo: Backend API v1 — Portal ArtFinal
stack: Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis · Horizon · Pulse · Docker/Laradock
publico: Desenvolvedores, QA, SRE
status: aprovado
---

# Setup de Ambiente de Desenvolvimento — Portal ArtFinal (Backend API v1)

Este documento descreve o passo a passo **obrigatório** para colocar o ambiente de desenvolvimento do Portal ArtFinal em execução, com foco na Backend API v1 descrita em [`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md). Cobre pré-requisitos, setup inicial, comandos do dia a dia, portas locais, troubleshooting e setup do editor.

Princípios que este documento materializa (ver planejamento §0):

1. API-first obrigatória — `api/v1` é a primeira coisa que subimos.
2. Core independente da camada HTTP — validamos com Horizon + testes de concorrência.
3. `declare(strict_types=1)` em 100% dos arquivos PHP.
4. Sem dados de cartão, sem IDs sequenciais na API pública.

---

## 1. Pré-requisitos

### 1.1 Sistemas operacionais suportados

| SO                                 | Situação      | Observação                                                 |
| ---------------------------------- | ------------- | ---------------------------------------------------------- |
| macOS 13+ (Intel ou Apple Silicon) | Suportado     | Laradock é a via oficial                                   |
| Linux (Debian 12+, Ubuntu 22.04+)  | Suportado     | Docker rodando direto                                      |
| Windows (WSL2 com Ubuntu 22.04+)   | Experiment    | Rodar o make dentro do WSL; Docker Desktop com WSL backend |
| Windows nativo (sem WSL)           | Não suportado | —                                                          |

### 1.2 Ferramentas obrigatórias (host)

| Ferramenta                | Versão mínima | Comando para verificar   | Instalação rápida (macOS)    |
| ------------------------- | ------------- | ------------------------ | ---------------------------- |
| Docker Desktop            | 4.30+         | `docker --version`       | `brew install --cask docker` |
| Docker Compose v2         | 2.29+         | `docker compose version` | vem com Docker Desktop       |
| Git                       | 2.40+         | `git --version`          | `brew install git`           |
| GNU Make                  | 3.81+         | `make --version`         | já vem no macOS              |
| Node.js                   | 20.x LTS      | `node -v`                | `brew install node@20`       |
| npm                       | 10.x          | `npm -v`                 | vem com Node                 |
| PHP (host, opcional)      | 8.4.x         | `php -v`                 | `brew install php@8.4`       |
| Composer (host, opcional) | 2.7+          | `composer --version`     | `brew install composer`      |

> PHP e Composer no host são opcionais — todos os comandos `php`/`composer` rodam dentro do container `workspace` via `make bash`. Só instale no host se precisar de IDE rodando Intelephense indexando `vendor/` local.

### 1.3 Configuração recomendada do Docker Desktop

| Recurso | Valor mínimo | Valor recomendado |
| ------- | ------------ | ----------------- |
| CPUs    | 4            | 6                 |
| Memória | 6 GB         | 8 GB              |
| Swap    | 1 GB         | 2 GB              |
| Disco   | 30 GB livres | 60 GB livres      |

No macOS Apple Silicon, desabilitar `Use Rosetta for x86_64 emulation` só depois de validar que a imagem PHP builda nativamente.

### 1.4 Dependências Composer extras exigidas pelo backend API v1

Além do `composer.json` atual, a fase F1 (Apêndice A do planejamento) exige instalar:

| Pacote                           | Motivo                                     |
| -------------------------------- | ------------------------------------------ |
| `laravel/sanctum`                | Auth SPA + mobile (§6.2)                   |
| `spatie/laravel-data`            | DTOs via `Data` (§3.3)                     |
| `saloonphp/laravel-plugin`       | Connectors HTTP para Itaú (§8.2)           |
| `sentry/sentry-laravel`          | Error tracking em produção (§12.2)         |
| `league/flysystem-aws-s3-v3`     | Storage privado + URL assinada (§8.4)      |
| `laravellegends/pt-br-validator` | Validação de CPF/CNPJ                      |
| `spatie/laravel-medialibrary`    | Uploads controlados                        |
| `dedoc/scramble`                 | OpenAPI automático (§2.12)                 |
| `spatie/laravel-query-builder`   | `filter[]`, `sort`, `page[cursor]` (§2.14) |

A instalação é feita no **passo 3 do setup inicial**.

---

## 2. Setup inicial — do zero ao primeiro boot

### 2.1 Passo 1 — Clone e branch

```bash
# Clone
git clone git@github.com:<ORG>/portalartfinal_v2.git
cd portalartfinal_v2

# Valide remote
git remote -v
# origin  git@github.com:<ORG>/portalartfinal_v2.git (fetch)
# origin  git@github.com:<ORG>/portalartfinal_v2.git (push)

# Checkout da branch base
git checkout main
git pull --ff-only
```

**Branch strategy resumida** (detalhes em `engineering-standards.md` §Branch strategy):

- `main` — produção; protegida; PR obrigatória; CI verde; 1 approve.
- `staging` — branch de homologação; recebe merges de `main` via release tag ou cherry-pick.
- `feature/<plane-id>-<descricao-kebab>` — trabalho em curso.
- `bugfix/<plane-id>-<descricao-kebab>` — correção não urgente.
- `hotfix/<plane-id>-<descricao-kebab>` — correção urgente em produção.

Para abrir uma feature:

```bash
git checkout -b feature/paf-42-reservar-assento-action
```

### 2.2 Passo 2 — `.env` e variáveis obrigatórias

```bash
cp .env.example .env
```

As variáveis abaixo **devem** estar preenchidas antes de `php artisan migrate`. Valores marcados como `<gerar>` são gerados no passo 4.

| Bloco                                   | Variável                      | Valor local                       | Observação                                   |
| --------------------------------------- | ----------------------------- | --------------------------------- | -------------------------------------------- |
| App                                     | `APP_NAME`                    | `"Portal ArtFinal"`               |                                              |
|                                         | `APP_ENV`                     | `local`                           | `production` em prod                         |
|                                         | `APP_KEY`                     | `<gerar>`                         | `php artisan key:generate`                   |
|                                         | `APP_URL`                     | `http://localhost`                |                                              |
|                                         | `APP_DEBUG`                   | `true`                            | `false` em prod/staging                      |
|                                         | `APP_TIMEZONE`                | `America/Sao_Paulo`               |                                              |
|                                         | `APP_LOCALE`                  | `pt_BR`                           |                                              |
| Banco (Postgres)                        | `DB_CONNECTION`               | `pgsql`                           |                                              |
|                                         | `DB_HOST`                     | `postgres`                        | nome do serviço Docker                       |
|                                         | `DB_PORT`                     | `5432`                            |                                              |
|                                         | `DB_DATABASE`                 | `portalartfinal`                  |                                              |
|                                         | `DB_USERNAME`                 | `portalartfinal`                  |                                              |
|                                         | `DB_PASSWORD`                 | `secret`                          |                                              |
| Redis                                   | `REDIS_HOST`                  | `redis`                           |                                              |
|                                         | `REDIS_PORT`                  | `6379`                            |                                              |
|                                         | `REDIS_PASSWORD`              | `null`                            |                                              |
|                                         | `REDIS_CLIENT`                | `phpredis`                        |                                              |
| Cache / Queue / Session                 | `CACHE_STORE`                 | `redis`                           |                                              |
|                                         | `SESSION_DRIVER`              | `redis`                           |                                              |
|                                         | `SESSION_LIFETIME`            | `120`                             |                                              |
|                                         | `SESSION_DOMAIN`              | `.localhost` (dev)                | `.portalartfinal.com.br` em prod             |
|                                         | `SESSION_SECURE_COOKIE`       | `false` (dev)                     | `true` em prod                               |
|                                         | `SESSION_SAME_SITE`           | `lax`                             |                                              |
|                                         | `QUEUE_CONNECTION`            | `redis`                           |                                              |
| Sanctum (planejamento §6.2)             | `SANCTUM_STATEFUL_DOMAINS`    | `localhost,localhost:3000`        | acrescentar domínio do React em staging/prod |
| Horizon (planejamento §7.2)             | `HORIZON_PREFIX`              | `portalartfinal_horizon:`         |                                              |
|                                         | `HORIZON_PATH`                | `horizon`                         |                                              |
| Mail                                    | `MAIL_MAILER`                 | `smtp`                            |                                              |
|                                         | `MAIL_HOST`                   | `mailpit`                         |                                              |
|                                         | `MAIL_PORT`                   | `1025`                            |                                              |
|                                         | `MAIL_FROM_ADDRESS`           | `no-reply@portalartfinal.local`   |                                              |
|                                         | `MAIL_FROM_NAME`              | `"Portal ArtFinal"`               |                                              |
| Gateway de pagamentos (planejamento §8) | `GATEWAY_DRIVER`              | `stub` (dev) / `itau` (prod)      |                                              |
|                                         | `GATEWAY_ITAU_BASE_URL`       | `https://api-sandbox.itau.com.br` |                                              |
|                                         | `GATEWAY_ITAU_TOKEN`          | `<vault>`                         | nunca versionar                              |
|                                         | `GATEWAY_ITAU_WEBHOOK_SECRET` | `<vault>`                         | usado no HMAC §5.5                           |
| Observabilidade (planejamento §12)      | `SENTRY_LARAVEL_DSN`          | vazio em dev                      | preencher em staging/prod                    |
|                                         | `SENTRY_TRACES_SAMPLE_RATE`   | `0.1`                             |                                              |
|                                         | `LOG_CHANNEL`                 | `stack`                           |                                              |
|                                         | `LOG_LEVEL`                   | `debug` (dev) / `info` (prod)     |                                              |
| Storage S3 (planejamento §8.4)          | `AWS_ACCESS_KEY_ID`           | `<vault>`                         |                                              |
|                                         | `AWS_SECRET_ACCESS_KEY`       | `<vault>`                         |                                              |
|                                         | `AWS_DEFAULT_REGION`          | `sa-east-1`                       |                                              |
|                                         | `AWS_BUCKET`                  | `artfinal-private`                | `-staging` / `-prod`                         |
|                                         | `AWS_USE_PATH_STYLE_ENDPOINT` | `false`                           |                                              |
|                                         | `AWS_URL`                     | vazio                             | preencher só se CDN                          |

> **Segurança:** NUNCA comitar `.env`. Em CI/CD, use secret manager (GitHub Actions Secrets + AWS Secrets Manager em produção). Detalhes em `security-operations.md` §Rotação de segredos.

### 2.3 Passo 3 — Subir os containers via Laradock

O projeto usa Laradock vendored em `laradock/` com patches locais. Para subir tudo:

```bash
# Primeira vez (boot completo guiado)
./docker-setup.sh

# A partir daí, no dia a dia:
make up          # sobe containers
make status      # confere ps
make logs        # streams dos logs
```

O script `docker-setup.sh` executa, em ordem:

1. `make build` — builda workspace + php-fpm + nginx (reaplicando os 3 patches documentados em `laradock/PATCHES.md`).
2. `make up` — sobe os serviços listados em `docs/INFRA.md §URLs`.
3. Aguarda PostgreSQL ficar healthy.
4. `composer install` dentro do workspace.
5. `php artisan key:generate` + `php artisan migrate`.
6. `php artisan horizon:install` + `php artisan pulse:install`.
7. `npm install && npm run build`.
8. `docker compose restart laravel-horizon`.

Se algum passo falhar, o script aborta. Rode novamente após resolver — é idempotente.

### 2.4 Passo 4 — Instalar pacotes Composer extras (F1)

Após o primeiro boot, entre no workspace e instale os pacotes listados em §1.4:

```bash
make bash
# dentro do workspace:
composer require \
  laravel/sanctum \
  spatie/laravel-data \
  saloonphp/laravel-plugin \
  sentry/sentry-laravel \
  league/flysystem-aws-s3-v3 \
  laravellegends/pt-br-validator \
  spatie/laravel-medialibrary \
  dedoc/scramble \
  spatie/laravel-query-builder

# Publicar configurações necessárias
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider"
php artisan vendor:publish --tag=media-library-config
php artisan sentry:publish --dsn="$SENTRY_LARAVEL_DSN"

# Migrar tabelas recém-criadas (personal_access_tokens, media, ...)
php artisan migrate
```

### 2.5 Passo 5 — Seeders de desenvolvimento

```bash
make bash
# dentro do workspace:
php artisan migrate:fresh --seed
```

O `DevelopmentSeeder` (ver §F5 do planejamento) cria:

- 1 organização, 1 instituição, 2 cursos, 3 turmas.
- 1 evento com mapa de 10 mesas × 8 assentos.
- 20 formandos (`portal_users` com role `formando`).
- Cotas padrão, 50 convites em estados variados, algumas reservas.
- 1 `admin_user` (login: `admin@artfinal.local` / senha: `admin123`).

Para adicionar novos cenários, estender `database/seeders/DevelopmentSeeder.php` — nunca rodar em produção.

### 2.6 Passo 6 — Horizon e workers

O container `laravel-horizon` do Laradock já executa `php artisan horizon` automaticamente. Se você quiser rodar workers adicionais dedicados em troubleshooting (sem Horizon):

```bash
make bash
# worker dedicado para a fila crítica
php artisan queue:work redis \
  --queue=critical-seating,webhooks,notifications,default \
  --tries=3 --timeout=90 --backoff=10,30,90
```

Em produção, a ordem de prioridade das filas e os supervisores são os do planejamento §7.2 (`critical-seating`, `webhooks`, `exports`, `notifications`, `default`).

### 2.7 Passo 7 — Vite dev server

Em outro terminal (fora do container) ou dentro do workspace:

```bash
make bash
# dentro do workspace:
npm run dev
```

O Vite escuta nas portas configuradas em `vite.config.js` e publica dois entry points (`admin.css`, `admin.js`, `portal.css`, `portal.js` — ver `CLAUDE.md §5.2`). Se surgir o erro `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, rode `npm run build` para gerar o `public/build/manifest.json`.

### 2.8 Passo 8 — Smoke test

```bash
# URLs principais respondendo
for u in http://localhost http://localhost/horizon http://localhost/pulse \
         http://localhost/api/v1/me http://localhost:5050 http://localhost:8125; do
  printf "%-40s " "$u"
  curl -s -o /dev/null -w "%{http_code}\n" "$u"
done
```

Esperado:

| URL                          | Status         | Motivo                                          |
| ---------------------------- | -------------- | ----------------------------------------------- |
| `http://localhost`           | `200`          | página raiz                                     |
| `http://localhost/horizon`   | `200` ou `302` | exige auth admin em staging/prod                |
| `http://localhost/pulse`     | `200` ou `302` | idem                                            |
| `http://localhost/api/v1/me` | `401`          | sem token, retorno esperado pelo envelope §2.11 |
| `http://localhost:5050`      | `302`          | redirect do pgAdmin para login                  |
| `http://localhost:8125`      | `200`          | UI Mailpit                                      |

Se `/api/v1/me` retornar `500` ou `404`, as rotas API ainda não estão registradas — verifique `bootstrap/app.php` (planejamento §2.1).

---

## 3. Makefile de referência — comandos diários

Todos os comandos abaixo já existem no `Makefile` do projeto e são os atalhos oficiais. Novos alvos são adicionados mediante PR.

| Comando               | O que faz                                                                                           |
| --------------------- | --------------------------------------------------------------------------------------------------- |
| `make up`             | Sobe todos os containers (`workspace php-fpm nginx postgres redis laravel-horizon pgadmin mailpit`) |
| `make down`           | Para todos os containers                                                                            |
| `make restart`        | Reinicia containers                                                                                 |
| `make build`          | `docker compose build` com reaplicação dos patches                                                  |
| `make bash`           | Abre shell no `workspace` (o lugar onde `php`, `composer`, `npm` rodam)                             |
| `make artisan <cmd>`  | `php artisan <cmd>` dentro do workspace                                                             |
| `make composer <cmd>` | `composer <cmd>` dentro do workspace                                                                |
| `make npm <cmd>`      | `npm <cmd>` dentro do workspace                                                                     |
| `make migrate`        | `php artisan migrate`                                                                               |
| `make fresh`          | `php artisan migrate:fresh --seed` (destrutivo — só em dev)                                         |
| `make seed`           | `php artisan db:seed`                                                                               |
| `make test`           | `php artisan test --compact`                                                                        |
| `make horizon`        | Reinicia o container `laravel-horizon`                                                              |
| `make logs`           | Stream `docker compose logs -f --tail=100`                                                          |
| `make status`         | `docker compose ps`                                                                                 |
| `make setup`          | Roda `./docker-setup.sh`                                                                            |

### 3.1 Alvos adicionais propostos (a incluir no Makefile em F1)

Os alvos abaixo serão adicionados pela issue PAF-XX do plano F1 (Apêndice A item 12 — hooks). Até lá, use os comandos explícitos.

| Alvo proposto          | Comando subjacente                                                    |
| ---------------------- | --------------------------------------------------------------------- |
| `make lint`            | `./vendor/bin/pint --format agent && npx prettier --check resources/` |
| `make lint-fix`        | `./vendor/bin/pint && npx prettier --write resources/`                |
| `make analyse`         | `./vendor/bin/phpstan analyse --memory-limit=1G`                      |
| `make quality`         | `make lint && make analyse && make test`                              |
| `make test-concurrent` | `php artisan test --parallel --processes=4`                           |
| `make pulse`           | `php artisan pulse:check` (executa snapshot agora)                    |
| `make pail`            | `php artisan pail` (tail em logs estruturados)                        |
| `make tinker`          | `php artisan tinker`                                                  |
| `make scramble`        | `php artisan scramble:export > storage/openapi.json`                  |
| `make docs-api`        | abre `http://localhost/docs/api`                                      |

> Enquanto esses alvos não existem, use diretamente via `make bash` + comando.

---

## 4. Portas locais e serviços

Referência única para saber "o que roda onde". Coincide com `docs/INFRA.md` e expande com API/docs.

| Serviço                 | URL/porta host                   | Container         | Observação                                     |
| ----------------------- | -------------------------------- | ----------------- | ---------------------------------------------- |
| Aplicação Laravel (web) | `http://localhost`               | `nginx + php-fpm` | raiz pública                                   |
| API v1 (REST)           | `http://localhost/api/v1/*`      | idem              | envelope padrão §2.11 do planejamento          |
| Webhooks                | `http://localhost/webhooks/*`    | idem              | sem CSRF; assinatura HMAC                      |
| Horizon dashboard       | `http://localhost/horizon`       | `laravel-horizon` | gate `web + auth:admin`                        |
| Pulse dashboard         | `http://localhost/pulse`         | `php-fpm`         | gate `web + auth:admin`                        |
| Scramble UI (OpenAPI)   | `http://localhost/docs/api`      | `php-fpm`         | `web + auth:admin` (§2.12)                     |
| Scramble JSON           | `http://localhost/docs/api.json` | idem              | spec bruta para orval/openapi-typescript       |
| pgAdmin                 | `http://localhost:5050`          | `pgadmin`         | login: `admin@artfinal.local` / `secret`       |
| Mailpit (UI)            | `http://localhost:8125`          | `mailpit`         | SMTP captura de e-mails                        |
| Mailpit (SMTP)          | `mailpit:1025` (interno)         | idem              | usado por `MAIL_HOST` na app                   |
| PostgreSQL              | `localhost:5432`                 | `postgres`        | `portalartfinal` / `portalartfinal` / `secret` |
| Redis                   | `localhost:6379`                 | `redis`           | sem senha em dev                               |
| Vite HMR                | `http://localhost:5173`          | host ou workspace | `npm run dev`                                  |

### 4.1 Conexões a partir do host

```bash
# Postgres do host
PGPASSWORD=secret psql -h localhost -U portalartfinal -d portalartfinal -c '\l'

# Redis do host
redis-cli -h localhost ping
```

---

## 5. Troubleshooting comum

> Quando um problema não estiver listado aqui, primeiro `make logs | grep -i error` e depois `runbook-operations.md §Respostas a incidentes`.

### 5.1 Erro de permissão em `storage/` ou `bootstrap/cache/`

Sintoma:

```
The stream or file "storage/logs/laravel.log" could not be opened: Permission denied
```

Fix:

```bash
make bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 5.2 Postgres recusando conexão

Sintoma:

```
SQLSTATE[08006] could not connect to server: Connection refused
```

Passos:

```bash
make status
# Se postgres ficar em Restarting:
cd laradock && docker compose logs postgres --tail=200

# Testar diretamente do workspace:
make bash
PGPASSWORD=secret psql -h postgres -U portalartfinal -d portalartfinal -c '\l'
```

Se o Postgres não sobe por corrupção de volume (raro), drop e recria **apenas em dev**:

```bash
cd laradock
docker compose down -v   # apaga volumes — DADOS PERDIDOS
cd ..
./docker-setup.sh
```

### 5.3 Redis em restart loop

Sintoma nos logs: `FATAL CONFIG FILE ERROR ... requirepass "--loadmodule"`.

Causa: patch 2 de `laradock/PATCHES.md` foi perdido em resync. Fix:

```bash
# Conferir e reaplicar o patch em laradock/docker-compose.yml
# (converter command: do redis para list form)
make build
make up
```

### 5.4 Sanctum não persiste cookie de sessão no SPA

Sintoma: chamadas do React viam `XHR` retornam 401 mesmo após `/sanctum/csrf-cookie + /auth/login`.

Checklist:

1. `SANCTUM_STATEFUL_DOMAINS` inclui exatamente o host:port do React (`localhost:3000`).
2. `SESSION_DOMAIN=.localhost` em dev (com o ponto inicial).
3. `SESSION_SAME_SITE=lax` — `none` só com HTTPS + `Secure=true`.
4. Requests do React enviam `withCredentials: true` (axios) ou `credentials: 'include'` (fetch).
5. CORS: `config/cors.php` tem `'supports_credentials' => true` e `'allowed_origins'` listando explicitamente o host do React (não `*`).

```bash
# Inspecionar o cookie de sessão
curl -v http://localhost/sanctum/csrf-cookie 2>&1 | grep -i set-cookie
```

### 5.5 Horizon não processa jobs

Sintoma: `/horizon` mostra jobs em pending e workers inativos.

```bash
# Reiniciar o container do Horizon
make horizon

# Se persistir, checar se config mudou e não foi recarregado:
make bash
php artisan horizon:terminate
php artisan horizon
```

Se um supervisor específico não sobe, checar `config/horizon.php` para typo em `queue` ou `connection`. Ver planejamento §7.2 para baseline.

### 5.6 `/horizon` ou `/pulse` retornando 502

Sintoma: `/` responde 200 mas dashboards caem em 502 após ~30s. Log `php-fpm` mostra SIGKILL.

Causa: `xdebug.mode=develop` instrumentando demais. Fix: patch 3 de `laradock/PATCHES.md` — manter apenas `xdebug.mode=debug`.

### 5.7 Vite manifest ausente

```
Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest: resources/css/admin.css
```

Fix:

```bash
make bash
npm run build     # gera public/build/manifest.json
# ou, se desenvolvendo com HMR:
npm run dev
```

### 5.8 Pint falhando em staged files

Sintoma: pre-commit hook falha com

```
Laravel Pint detected code style issues
```

Fix:

```bash
./vendor/bin/pint --format agent
# Depois faz o add e o commit novamente:
git add -A
git commit
```

### 5.9 PHPStan reclamando de tipos em `$casts`

Sintoma: `Property App\Models\Seating\ReservaAssento::$casts has no return type specified.`

Causa: PHPStan level 6 exige PHPDoc. Fix:

```php
/** @var array<string, string> */
protected $casts = [
    'status' => StatusReserva::class,
    ...
];
```

### 5.10 Testes intermitentes em seating

Se `tests/Feature/Seating/ConcorrenciaTest.php` der flaky, provavelmente o `RefreshDatabase` está em colisão com transação aninhada da action. Solução:

- Garanta que o teste usa `DatabaseTruncation` ou `RefreshDatabase` sem transação quando testa lock real.
- Para teste de concorrência real, use `--parallel --processes=4` e `--coverage --compact`.

Mais detalhes em `engineering-standards.md §Código de concorrência`.

---

## 6. Setup de editor recomendado

### 6.1 VS Code

Instalar as extensões abaixo. O projeto inclui `.vscode/extensions.json` sugerindo-as; abrir o workspace dispara o prompt.

| Extensão                   | ID                                            | Função                       |
| -------------------------- | --------------------------------------------- | ---------------------------- |
| PHP Intelephense           | `bmewburn.vscode-intelephense-client`         | Intellisense PHP, type-aware |
| PHP Namespace Resolver     | `mehedidracula.php-namespace-resolver`        | Auto-import                  |
| Laravel Extra Intellisense | `amiralizadeh9480.laravel-extra-intellisense` | routes(), config(), views    |
| Laravel Blade formatter    | `shufo.vscode-blade-formatter`                | auto-format Blade            |
| Tailwind CSS IntelliSense  | `bradlc.vscode-tailwindcss`                   | classes utilitárias          |
| Pest Snippets              | `nunomaduro.vscode-pest-snippets`             | Scaffolds de teste Pest      |
| PHPStan                    | `SanderRonde.phpstan-vscode`                  | Inline diagnostics           |
| ESLint                     | `dbaeumer.vscode-eslint`                      | JS/TS                        |
| Prettier                   | `esbenp.prettier-vscode`                      | Formata JS/CSS/MD            |
| DotENV                     | `mikestead.dotenv`                            | Highlight .env               |
| EditorConfig               | `EditorConfig.EditorConfig`                   | Respeita `.editorconfig`     |

### 6.2 Settings do workspace

```jsonc
// .vscode/settings.json (já versionado)
{
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "[php]": {
        "editor.defaultFormatter": "bmewburn.vscode-intelephense-client",
    },
    "[blade]": {
        "editor.defaultFormatter": "shufo.vscode-blade-formatter",
    },
    "intelephense.environment.phpVersion": "8.4.0",
    "intelephense.files.maxSize": 5000000,
    "tailwindCSS.includeLanguages": { "blade": "html" },
    "eslint.validate": ["javascript", "javascriptreact", "typescript", "typescriptreact"],
}
```

### 6.3 PhpStorm

- Marcar `app/` como Source Root, `tests/` como Test Source Root.
- Activate: Laravel Plugin, EditorConfig, `.env files support`, Blade, Tailwind CSS.
- Code Style → PHP → From predefined style: **PSR-12**.
- File Watcher para Pint (`./vendor/bin/pint`) em `*.php` alterado.

### 6.4 JetBrains / outros — config mínima

- PHP 8.4 como interpreter (via Docker `workspace`).
- PHPStan level 6 como inspection provider.
- Pest como test runner.

---

## 7. Primeiros comandos do dia a dia

### 7.1 Começando o dia

```bash
make up
make status               # conferir todos healthy
make bash
php artisan migrate        # aplica migrations novas
php artisan horizon:terminate  # faz Horizon recarregar config
exit
```

### 7.2 Antes de commitar

```bash
make bash
./vendor/bin/pint --dirty --format agent       # só arquivos alterados
./vendor/bin/phpstan analyse --memory-limit=1G
php artisan test --compact
exit
git add -p
git commit                # hook pre-commit repete pint + prettier em staged files
```

### 7.3 Antes de abrir PR

```bash
# a partir do host
make bash
make quality              # lint + phpstan + test (após F1 quando o alvo existir)
exit
git push -u origin feature/paf-42-reservar-assento-action
gh pr create --base main --fill
```

---

## 8. Referências cruzadas

| Documento                                                                        | Papel                              |
| -------------------------------------------------------------------------------- | ---------------------------------- |
| [`CLAUDE.md`](../../CLAUDE.md)                                                   | Regras de projeto                  |
| [`docs/INFRA.md`](../INFRA.md)                                                   | Infra do Laradock + patches        |
| [`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) | Plano técnico canônico             |
| [`docs/devops/engineering-standards.md`](./engineering-standards.md)             | Padrões de código, commits, review |
| [`docs/devops/ci-cd.md`](./ci-cd.md)                                             | Pipeline CI/CD                     |
| [`docs/devops/runbook-deploy.md`](./runbook-deploy.md)                           | Runbook de deploy                  |
| [`docs/devops/runbook-operations.md`](./runbook-operations.md)                   | Runbook de operação 24x7           |
| [`docs/devops/monitoring-alerts.md`](./monitoring-alerts.md)                     | Monitoramento e alertas            |
| [`docs/devops/security-operations.md`](./security-operations.md)                 | Segurança operacional              |

---

## 9. FAQ rápido

**P: Posso rodar sem Docker?**
R: Não suportado. Laradock é a via oficial — ele garante paridade com staging/prod.

**P: Posso mudar as portas locais?**
R: Sim, editando `laradock/.env`. Documente a mudança em PR porque outros devs assumem os defaults.

**P: Como reseto TUDO?**
R: `make down && cd laradock && docker compose down -v && cd .. && ./docker-setup.sh`. Isso apaga volumes — **cuidado com dados locais**.

**P: Horizon e Pulse aparecem em branco.**
R: Em dev/local não tem auth; em staging/prod o gate `auth:admin` bloqueia se você não estiver logado. Faça login em `/admin/login` primeiro.

**P: Mudei `config/auth.php`, por que continua com comportamento antigo?**
R: `php artisan config:clear` dentro do workspace. Em dev nunca rode `config:cache`.

**P: Meus testes ficam muito lentos.**
R: Use `php artisan test --parallel --processes=4`. Garanta Redis e Postgres separados por processo via `TESTING_DB_CONNECTION` conforme Pest 4 docs.

---

Última atualização: 2026-04-17 · Responsável por manter: time DevOps (time on-call).
