---
titulo: Setup de Ambiente de Desenvolvimento
versao: 1.0.0
data: 2026-04-17
autores:
    - DevOps Engineering
stack: Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis · Horizon · Pulse · Docker/Laradock
publico: Desenvolvedores, QA, SRE
status: aprovado
---

# Setup de Ambiente de Desenvolvimento

Este documento descreve o passo a passo **obrigatório** para colocar o ambiente de desenvolvimento da aplicação em execução. Cobre pré-requisitos, setup inicial, comandos do dia a dia, portas locais, troubleshooting e setup do editor.

Princípios que este documento materializa:

1. Toda a UI é server-side: Blade + Livewire + Alpine.js.
2. `declare(strict_types=1)` em 100% dos arquivos PHP.
3. Paridade com produção via Docker/Laradock.

---

## 1. Pré-requisitos

### 1.1 Sistemas operacionais suportados

| SO                                 | Situação      | Observação                                                 |
| ---------------------------------- | ------------- | ---------------------------------------------------------- |
| macOS 13+ (Intel ou Apple Silicon) | Suportado     | Laradock é a via oficial                                   |
| Linux (Debian 12+, Ubuntu 22.04+)  | Suportado     | Docker rodando direto                                      |
| Windows (WSL2 com Ubuntu 22.04+)   | Experimental  | Rodar o make dentro do WSL; Docker Desktop com WSL backend |
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

---

## 2. Setup inicial — do zero ao primeiro boot

### 2.1 Passo 1 — Clone e branch

```bash
# Clone
git clone git@github.com:<ORG>/<REPO>.git
cd <REPO>

# Valide remote
git remote -v

# Checkout da branch base
git checkout main
git pull --ff-only
```

**Branch strategy resumida** (detalhes em `conventions.md §2`):

- `main` — produção; protegida; PR obrigatória; CI verde; 1 approve.
- `feature/<descricao-kebab>` — trabalho em curso.
- `fix/<descricao-kebab>` — correção não urgente.
- `hotfix/<descricao-kebab>` — correção urgente em produção.

Para abrir uma feature:

```bash
git checkout -b feature/listagem-clientes
```

### 2.2 Passo 2 — `.env` e variáveis obrigatórias

```bash
cp .env.example .env
```

As variáveis abaixo **devem** estar preenchidas antes de `php artisan migrate`. Valores marcados como `<gerar>` são gerados no passo 4.

| Bloco                   | Variável                | Valor local           | Observação                       |
| ----------------------- | ----------------------- | --------------------- | -------------------------------- |
| App                     | `APP_NAME`              | `"Laravel Admin"`     |                                  |
|                         | `APP_ENV`               | `local`               | `production` em prod             |
|                         | `APP_KEY`               | `<gerar>`             | `php artisan key:generate`       |
|                         | `APP_URL`               | `http://localhost`    |                                  |
|                         | `APP_DEBUG`             | `true`                | `false` em prod/staging          |
|                         | `APP_TIMEZONE`          | `America/Sao_Paulo`   |                                  |
|                         | `APP_LOCALE`            | `pt_BR`               |                                  |
| Banco (Postgres)        | `DB_CONNECTION`         | `pgsql`               |                                  |
|                         | `DB_HOST`               | `postgres`            | nome do serviço Docker           |
|                         | `DB_PORT`               | `5432`                |                                  |
|                         | `DB_DATABASE`           | `app`                 |                                  |
|                         | `DB_USERNAME`           | `app`                 |                                  |
|                         | `DB_PASSWORD`           | `secret`              |                                  |
| Redis                   | `REDIS_HOST`            | `redis`               |                                  |
|                         | `REDIS_PORT`            | `6379`                |                                  |
|                         | `REDIS_PASSWORD`        | `null`                |                                  |
|                         | `REDIS_CLIENT`          | `phpredis`            |                                  |
| Cache / Queue / Session | `CACHE_STORE`           | `redis`               |                                  |
|                         | `SESSION_DRIVER`        | `redis`               |                                  |
|                         | `SESSION_LIFETIME`      | `120`                 |                                  |
|                         | `SESSION_DOMAIN`        | `.localhost` (dev)    | domínio real em prod             |
|                         | `SESSION_SECURE_COOKIE` | `false` (dev)         | `true` em prod                   |
|                         | `SESSION_SAME_SITE`     | `lax`                 |                                  |
|                         | `QUEUE_CONNECTION`      | `redis`               |                                  |
| Horizon                 | `HORIZON_PREFIX`        | `app_horizon:`        |                                  |
|                         | `HORIZON_PATH`          | `horizon`             |                                  |
| Mail                    | `MAIL_MAILER`           | `smtp`                |                                  |
|                         | `MAIL_HOST`             | `mailpit`             |                                  |
|                         | `MAIL_PORT`             | `1025`                |                                  |
|                         | `MAIL_FROM_ADDRESS`     | `no-reply@app.local`  |                                  |
|                         | `MAIL_FROM_NAME`        | `"Laravel Admin"`     |                                  |
| Observabilidade         | `LOG_CHANNEL`           | `stack`               |                                  |
|                         | `LOG_LEVEL`             | `debug` (dev)         | `info` em prod                   |

> **Segurança:** NUNCA comitar `.env`. Em CI/CD, use secret manager (GitHub Actions Secrets + secret manager do provedor em produção).

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

1. `make build` — builda workspace + php-fpm + nginx (reaplicando os patches documentados em `laradock/PATCHES.md`).
2. `make up` — sobe os serviços listados em `docs/devops/infra.md §URLs`.
3. Aguarda PostgreSQL ficar healthy.
4. `composer install` dentro do workspace.
5. `php artisan key:generate` + `php artisan migrate`.
6. `php artisan horizon:install` + `php artisan pulse:install`.
7. `npm install && npm run build`.
8. `docker compose restart laravel-horizon`.

Se algum passo falhar, o script aborta. Rode novamente após resolver — é idempotente.

### 2.4 Passo 4 — Seeders de desenvolvimento

```bash
make bash
# dentro do workspace:
php artisan migrate:fresh --seed
```

O seed cria os usuários de desenvolvimento:

- `admin@example.com` / `password` — role `super-admin`.
- `gestor@example.com` / `password` — role `gestor`.

Para adicionar novos cenários, estender os seeders em `database/seeders/` — nunca rodar `migrate:fresh` em produção.

### 2.5 Passo 5 — Horizon e workers

O container `laravel-horizon` do Laradock já executa `php artisan horizon` automaticamente. Se você quiser rodar workers adicionais dedicados em troubleshooting (sem Horizon):

```bash
make bash
php artisan queue:work redis \
  --queue=emails,exports,pdf,default \
  --tries=3 --timeout=90 --backoff=10,30,90
```

As filas padrão são `default`, `emails`, `exports` e `pdf` (ver `conventions.md §10`).

### 2.6 Passo 6 — Vite dev server

Em outro terminal (fora do container) ou dentro do workspace:

```bash
make bash
# dentro do workspace:
npm run dev
```

O Vite escuta nas portas configuradas em `vite.config.js` e publica os entry points do admin (`resources/css/admin.css`, `resources/js/admin.js`). Se surgir o erro `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, rode `npm run build` para gerar o `public/build/manifest.json`.

### 2.7 Passo 7 — Smoke test

```bash
# URLs principais respondendo
for u in http://localhost http://localhost/admin http://localhost/horizon \
         http://localhost/pulse http://localhost:5050 http://localhost:8125; do
  printf "%-40s " "$u"
  curl -s -o /dev/null -w "%{http_code}\n" "$u"
done
```

Esperado:

| URL                        | Status         | Motivo                            |
| -------------------------- | -------------- | --------------------------------- |
| `http://localhost`         | `302`          | redireciona para `/admin`         |
| `http://localhost/admin`   | `200` ou `302` | login admin se não autenticado    |
| `http://localhost/horizon` | `200` ou `302` | exige auth admin em staging/prod  |
| `http://localhost/pulse`   | `200` ou `302` | idem                              |
| `http://localhost:5050`    | `302`          | redirect do pgAdmin para login    |
| `http://localhost:8125`    | `200`          | UI Mailpit                        |

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
| `make lint`           | `./vendor/bin/pint --format agent && npx prettier --check resources/`                              |
| `make quality`        | `make lint && ./vendor/bin/phpstan analyse && make test`                                            |
| `make setup`          | Roda `./docker-setup.sh`                                                                            |

---

## 4. Portas locais e serviços

Referência única para saber "o que roda onde". Coincide com `docs/devops/infra.md`.

| Serviço                 | URL/porta host           | Container         | Observação                          |
| ----------------------- | ------------------------ | ----------------- | ----------------------------------- |
| Aplicação Laravel (web) | `http://localhost`       | `nginx + php-fpm` | redireciona para `/admin`           |
| Admin                   | `http://localhost/admin` | idem              | painel backoffice                   |
| Horizon dashboard       | `http://localhost/horizon` | `laravel-horizon` | gate `web + auth:admin`           |
| Pulse dashboard         | `http://localhost/pulse` | `php-fpm`         | gate `web + auth:admin`             |
| pgAdmin                 | `http://localhost:5050`  | `pgadmin`         | login configurado no Laradock       |
| Mailpit (UI)            | `http://localhost:8125`  | `mailpit`         | captura de e-mails                  |
| Mailpit (SMTP)          | `mailpit:1025` (interno) | idem              | usado por `MAIL_HOST` na app        |
| PostgreSQL              | `localhost:5432`         | `postgres`        | conforme `.env`                     |
| Redis                   | `localhost:6379`         | `redis`           | sem senha em dev                    |
| Vite HMR                | `http://localhost:5173`  | host ou workspace | `npm run dev`                       |

### 4.1 Conexões a partir do host

```bash
# Postgres do host (ajuste user/db conforme o .env)
PGPASSWORD=secret psql -h localhost -U app -d app -c '\l'

# Redis do host
redis-cli -h localhost ping
```

---

## 5. Troubleshooting comum

> Quando um problema não estiver listado aqui, primeiro `make logs | grep -i error`.

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

# Testar diretamente do workspace (ajuste user/db conforme o .env):
make bash
PGPASSWORD=secret psql -h postgres -U app -d app -c '\l'
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

Causa: patch de `laradock/PATCHES.md` foi perdido em resync. Fix:

```bash
# Conferir e reaplicar o patch em laradock/docker-compose.yml
# (converter command: do redis para list form)
make build
make up
```

### 5.4 Horizon não processa jobs

Sintoma: `/horizon` mostra jobs em pending e workers inativos.

```bash
# Reiniciar o container do Horizon
make horizon

# Se persistir, checar se config mudou e não foi recarregado:
make bash
php artisan horizon:terminate
php artisan horizon
```

Se um supervisor específico não sobe, checar `config/horizon.php` para typo em `queue` ou `connection`.

### 5.5 `/horizon` ou `/pulse` retornando 502

Sintoma: `/` responde 200 mas dashboards caem em 502 após ~30s. Log `php-fpm` mostra SIGKILL.

Causa: `xdebug.mode=develop` instrumentando demais. Fix: ver `laradock/PATCHES.md` — manter apenas `xdebug.mode=debug`.

### 5.6 Vite manifest ausente

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

### 5.7 Pint falhando em staged files

Sintoma: pre-commit hook falha com `Laravel Pint detected code style issues`.

Fix:

```bash
./vendor/bin/pint --format agent
# Depois faz o add e o commit novamente:
git add -A
git commit
```

### 5.8 PHPStan reclamando de tipos em `$casts`

Sintoma: `Property App\Models\Cliente::$casts has no return type specified.`

Causa: PHPStan level 6 exige PHPDoc. Fix:

```php
/** @var array<string, string> */
protected $casts = [
    'status' => StatusCliente::class,
];
```

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
}
```

### 6.3 PhpStorm

- Marcar `app/` como Source Root, `tests/` como Test Source Root.
- Activate: Laravel Plugin, EditorConfig, `.env files support`, Blade, Tailwind CSS.
- Code Style → PHP → From predefined style: **PSR-12**.
- File Watcher para Pint (`./vendor/bin/pint`) em `*.php` alterado.

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
make bash
make quality              # lint + phpstan + test
exit
git push -u origin feature/listagem-clientes
gh pr create --base main --fill
```

---

## 8. Referências cruzadas

| Documento                                            | Papel                              |
| ---------------------------------------------------- | ---------------------------------- |
| [`CLAUDE.md`](../../CLAUDE.md)                       | Regras de projeto                  |
| [`docs/devops/infra.md`](infra.md)                   | Infra do Laradock + patches        |
| [`docs/devops/conventions.md`](conventions.md)       | Padrões de código, commits, review |
| [`docs/devops/tools-and-packages.md`](tools-and-packages.md) | Ferramentas e pacotes      |

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
R: Use `php artisan test --parallel --processes=4`.

---

Última atualização: 2026-04-17 · Responsável por manter: time DevOps.
