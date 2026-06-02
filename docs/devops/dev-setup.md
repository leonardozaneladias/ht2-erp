---
titulo: Setup de Ambiente de Desenvolvimento
versao: 2.0.0
data: 2026-06-02
autores:
    - DevOps Engineering
stack: Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis · Horizon · Pulse · DDEV + OrbStack
publico: Desenvolvedores, QA, SRE
status: aprovado
---

# Setup de Ambiente de Desenvolvimento

Este documento descreve o passo a passo **obrigatório** para colocar o ambiente de desenvolvimento da aplicação em execução. Cobre pré-requisitos, setup inicial, comandos do dia a dia, portas locais, troubleshooting e setup do editor.

O ambiente oficial é o **DDEV** (config versionada em `.ddev/`), usando **OrbStack** como provider Docker no macOS. A configuração vive no repositório — quem clona obtém exatamente o mesmo ambiente.

Princípios que este documento materializa:

1. Toda a UI é server-side: Blade + Livewire + Alpine.js.
2. `declare(strict_types=1)` em 100% dos arquivos PHP.
3. Ambiente reproduzível e portável via DDEV.

---

## 1. Pré-requisitos

### 1.1 Sistemas operacionais suportados

| SO                                 | Situação      | Observação                                           |
| ---------------------------------- | ------------- | ---------------------------------------------------- |
| macOS 13+ (Intel ou Apple Silicon) | Suportado     | OrbStack é a via oficial (provider Docker)           |
| Linux (Debian 12+, Ubuntu 22.04+)  | Suportado     | DDEV com Docker Engine nativo                        |
| Windows (WSL2 com Ubuntu 22.04+)   | Suportado     | DDEV dentro do WSL2 + Docker Desktop com WSL backend |
| Windows nativo (sem WSL)           | Não suportado | —                                                    |

### 1.2 Ferramentas obrigatórias (host)

| Ferramenta | Versão mínima | Comando para verificar | Instalação rápida (macOS)     |
| ---------- | ------------- | ---------------------- | ----------------------------- |
| OrbStack   | 1.x           | `orb version`          | `brew install orbstack`       |
| DDEV       | 1.24+         | `ddev version`         | `brew install ddev/ddev/ddev` |
| Git        | 2.40+         | `git --version`        | `brew install git`            |
| GNU Make   | 3.81+         | `make --version`       | já vem no macOS               |

> **PHP, Composer, Node e npm NÃO precisam ser instalados no host** — o DDEV provê PHP 8.4, Composer e Node 22 dentro do container. Use-os via `ddev php`, `ddev composer`, `ddev npm`. Só instale PHP no host se quiser a IDE indexando `vendor/` com Intelephense.

No Windows/Linux, substitua o OrbStack por Docker Desktop (ou Docker Engine no Linux); o `.ddev/` é idêntico.

### 1.3 Instalação do OrbStack (macOS)

```bash
brew install orbstack
```

Abra o app **uma vez** e selecione "Docker". O OrbStack assume o contexto Docker automaticamente (não precisa de `docker context use`). Tem migração embutida do Docker Desktop, se você já o usava.

`performance_mode` fica no default do macOS (Mutagen) — OrbStack + Mutagen é a combinação mais rápida nos benchmarks oficiais do DDEV.

---

## 2. Setup inicial — do zero ao primeiro boot

### 2.1 Passo 1 — Clone e branch

```bash
git clone git@github.com:<ORG>/<REPO>.git
cd <REPO>
git remote -v
git checkout main && git pull --ff-only
```

**Branch strategy resumida** (detalhes em `conventions.md §2`):

- `main` — produção; protegida; PR obrigatória; CI verde; 1 approve.
- `feature/<descricao-kebab>` — trabalho em curso.
- `fix/<descricao-kebab>` — correção não urgente.
- `hotfix/<descricao-kebab>` — correção urgente em produção.

```bash
git checkout -b feature/listagem-clientes
```

### 2.2 Passo 2 — `.env`

```bash
cp .env.example .env
```

O `.env.example` já vem alinhado ao DDEV. As credenciais de **banco** são geridas pelo próprio DDEV (reescritas no `ddev start`) — você não precisa ajustá-las. Variáveis-chave:

| Bloco                   | Variável           | Valor local                 | Observação                                |
| ----------------------- | ------------------ | --------------------------- | ----------------------------------------- |
| App                     | `APP_NAME`         | `"Laravel Admin"`           |                                           |
|                         | `APP_ENV`          | `local`                     | `production` em prod                      |
|                         | `APP_KEY`          | `<gerar>`                   | `make setup` roda `key:generate`          |
|                         | `APP_URL`          | `https://gdf-erp.ddev.site` | bate com o `name` do `.ddev/config.yaml`  |
|                         | `APP_DEBUG`        | `true`                      | `false` em prod/staging                   |
|                         | `APP_LOCALE`       | `pt_BR`                     |                                           |
| Banco (Postgres)        | `DB_CONNECTION`    | `pgsql`                     | gerido pelo DDEV                          |
|                         | `DB_HOST`          | `db`                        | hostname interno do DDEV                  |
|                         | `DB_DATABASE`      | `db`                        | gerido pelo DDEV                          |
|                         | `DB_USERNAME`      | `db`                        | gerido pelo DDEV                          |
|                         | `DB_PASSWORD`      | `db`                        | gerido pelo DDEV                          |
| Redis                   | `REDIS_HOST`       | `redis`                     | serviço `.ddev/docker-compose.redis.yaml` |
|                         | `REDIS_PORT`       | `6379`                      |                                           |
| Cache / Queue / Session | `CACHE_STORE`      | `redis`                     |                                           |
|                         | `SESSION_DRIVER`   | `redis`                     |                                           |
|                         | `QUEUE_CONNECTION` | `redis`                     |                                           |
| Horizon                 | `HORIZON_PREFIX`   | `laravel_admin_horizon:`    |                                           |
| Mail                    | `MAIL_MAILER`      | `smtp`                      |                                           |
|                         | `MAIL_HOST`        | `localhost`                 | Mailpit embutido (container web)          |
|                         | `MAIL_PORT`        | `1025`                      |                                           |

> **Segurança:** NUNCA comitar `.env`. Em CI/CD, use secret manager (GitHub Actions Secrets + secret manager do provedor em produção).

### 2.3 Passo 3 — Subir o ambiente

```bash
ddev start       # sobe containers; hooks rodam composer install, migrate --force, npm install
make setup       # 1x: key:generate, migrate --seed, assets Horizon/Pulse, npm build
```

O que acontece no `ddev start`:

1. Sobe os containers (web com PHP 8.4 + Nginx, `db` PostgreSQL 16, `redis`).
2. Reescreve as credenciais de banco no `.env` (project type `laravel`).
3. Sobe o Horizon como daemon persistente (`web_extra_daemons`).
4. Roda os hooks `post-start`: `composer install`, `php artisan migrate --force`, `npm install`.

O `make setup` complementa o que não é idempotente (chave, seed, publicação de assets de Horizon/Pulse e build de produção).

### 2.4 Passo 4 — Seeders de desenvolvimento

```bash
make fresh       # ddev artisan migrate:fresh --seed + DevelopmentSeeder
```

O seed cria os usuários de desenvolvimento:

- `admin@example.com` / `password` — role `super-admin`.
- `gestor@example.com` / `password` — role `gestor`.

Para adicionar novos cenários, estender os seeders em `database/seeders/` — nunca rodar `migrate:fresh` em produção.

### 2.5 Passo 5 — Horizon e workers

O Horizon já roda como daemon dentro do container web (`web_extra_daemons` em `.ddev/config.yaml`). Para gerenciar:

```bash
ddev exec supervisorctl status                       # lista webextradaemons:horizon
make horizon                                         # reinicia o daemon após mudar config
```

As filas padrão são `default`, `emails`, `exports` e `pdf` (ver `conventions.md §10`).

### 2.6 Passo 6 — Vite dev server

```bash
make dev         # = ddev npm run dev
```

O Vite é exposto via `web_extra_exposed_ports` (config em `vite.config.js`, bloco `server`) e publica os entry points do admin em `https://gdf-erp.ddev.site:5173` com HMR. Se surgir `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, rode `ddev npm run build` para gerar `public/build/manifest.json`.

### 2.7 Passo 7 — Smoke test

```bash
for u in https://gdf-erp.ddev.site https://gdf-erp.ddev.site/admin \
         https://gdf-erp.ddev.site/horizon https://gdf-erp.ddev.site/pulse; do
  printf "%-44s " "$u"
  curl -sk -o /dev/null -w "%{http_code}\n" "$u"
done
ddev mailpit     # abre a UI do Mailpit
```

Esperado:

| URL                                 | Status         | Motivo                           |
| ----------------------------------- | -------------- | -------------------------------- |
| `https://gdf-erp.ddev.site`         | `302`          | redireciona para `/admin`        |
| `https://gdf-erp.ddev.site/admin`   | `200` ou `302` | login admin se não autenticado   |
| `https://gdf-erp.ddev.site/horizon` | `200` ou `302` | exige auth admin em staging/prod |
| `https://gdf-erp.ddev.site/pulse`   | `200` ou `302` | idem                             |

---

## 3. Makefile de referência — comandos diários

Todos os comandos abaixo são wrappers do `ddev` e os atalhos oficiais. Novos alvos são adicionados mediante PR.

| Comando               | O que faz                                        |
| --------------------- | ------------------------------------------------ |
| `make up`             | `ddev start`                                     |
| `make down`           | `ddev stop`                                      |
| `make restart`        | `ddev restart`                                   |
| `make bash`           | Shell no container web (`ddev ssh`)              |
| `make artisan <cmd>`  | `ddev artisan <cmd>`                             |
| `make composer <cmd>` | `ddev composer <cmd>`                            |
| `make npm <cmd>`      | `ddev npm <cmd>`                                 |
| `make dev`            | Vite dev server (`ddev npm run dev`)             |
| `make migrate`        | `ddev artisan migrate`                           |
| `make fresh`          | `migrate:fresh --seed` + DevelopmentSeeder (dev) |
| `make seed`           | `ddev artisan db:seed`                           |
| `make test`           | `ddev artisan test`                              |
| `make horizon`        | Reinicia o daemon Horizon (supervisorctl)        |
| `make logs`           | `ddev logs -f`                                   |
| `make status`         | `ddev describe`                                  |
| `make lint`           | Pint + Prettier                                  |
| `make quality`        | Lint + PHPStan + Test                            |
| `make setup`          | Setup inicial (key, seed, assets, build)         |

---

## 4. Portas locais e serviços

Referência única para saber "o que roda onde". Coincide com `docs/devops/infra.md`.

| Serviço                 | URL / Comando                       | Container | Observação                        |
| ----------------------- | ----------------------------------- | --------- | --------------------------------- |
| Aplicação Laravel (web) | `https://gdf-erp.ddev.site`         | `web`     | redireciona para `/admin`         |
| Admin                   | `https://gdf-erp.ddev.site/admin`   | `web`     | painel backoffice                 |
| Horizon dashboard       | `https://gdf-erp.ddev.site/horizon` | `web`     | gate `web + auth:admin`           |
| Pulse dashboard         | `https://gdf-erp.ddev.site/pulse`   | `web`     | gate `web + auth:admin`           |
| Mailpit (UI)            | `ddev mailpit`                      | `web`     | captura de e-mails                |
| Mailpit (SMTP)          | `localhost:1025` (interno)          | `web`     | usado por `MAIL_HOST` na app      |
| PostgreSQL              | `ddev psql` · interno `db:5432`     | `db`      | porta no host via `ddev describe` |
| Redis                   | interno `redis:6379`                | `redis`   | sem senha em dev                  |
| Vite HMR                | `https://gdf-erp.ddev.site:5173`    | `web`     | `make dev`                        |

### 4.1 Conexões a partir do host

```bash
ddev psql                 # cliente psql direto no banco
ddev describe             # mostra portas expostas no host (db, mailpit, etc.)
ddev redis-cli ping       # se o add-on expõe o comando; senão: ddev exec redis-cli -h redis ping
```

---

## 5. Troubleshooting comum

> Quando um problema não estiver listado aqui, primeiro `ddev logs | grep -i error`.

### 5.1 `ddev start` falha

```bash
ddev logs                 # logs do container web
ddev poweroff && ddev start
```

Confirme que o OrbStack está rodando (`orb status`) e é o contexto Docker ativo (`docker context show` → `orbstack`).

### 5.2 Erro de permissão em `storage/` ou `bootstrap/cache/`

```bash
ddev exec chmod -R 775 storage bootstrap/cache
```

### 5.3 Postgres recusando conexão

```bash
ddev describe             # confirma o serviço db healthy
ddev logs -s db           # logs do container de banco
ddev psql -c '\l'         # lista os bancos
```

Reset **apenas em dev** (apaga volumes/dados):

```bash
ddev delete -O            # remove containers e volumes do projeto, mantém o código
ddev start && make setup
```

### 5.4 Horizon não processa jobs

```bash
make horizon              # reinicia o daemon
ddev exec supervisorctl status
# Se persistir, recarregar config:
ddev artisan horizon:terminate
```

Se um supervisor específico não sobe, checar `config/horizon.php` para typo em `queue` ou `connection`.

### 5.5 `/horizon` ou `/pulse` retornando erro

Confirme que o Redis está no ar e que o `.env` usa `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`:

```bash
ddev exec php artisan tinker --execute="echo config('database.redis.default.host');"  # deve imprimir: redis
```

### 5.6 Vite manifest ausente

```
Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest: resources/css/admin.css
```

```bash
ddev npm run build        # gera public/build/manifest.json
# ou, desenvolvendo com HMR:
make dev
```

### 5.7 Pint falhando em staged files

```bash
ddev exec ./vendor/bin/pint
git add -A && git commit
```

### 5.8 PHPStan reclamando de tipos em `$casts`

PHPStan level 6 exige PHPDoc:

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

> Para o Intelephense indexar `vendor/`, ele precisa dos pacotes no host. Rode `ddev composer install` (instala no projeto montado) — o `vendor/` fica visível no host via o mount do DDEV.

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
- DDEV expõe um CLI interpreter remoto — aponte o PHP interpreter do PhpStorm para o container (`ddev` integration) se quiser rodar testes/debug pela IDE.

---

## 7. Primeiros comandos do dia a dia

### 7.1 Começando o dia

```bash
make up                   # ddev start (aplica migrations via hook)
make status               # ddev describe — confere serviços
```

### 7.2 Antes de commitar

```bash
ddev exec ./vendor/bin/pint --dirty            # só arquivos alterados
ddev exec ./vendor/bin/phpstan analyse --memory-limit=1G
make test
git add -p
git commit                # hook pre-commit repete pint + prettier em staged files
```

### 7.3 Antes de abrir PR

```bash
make quality              # lint + phpstan + test
git push -u origin feature/listagem-clientes
gh pr create --base main --fill
```

---

## 8. Referências cruzadas

| Documento                                                    | Papel                                      |
| ------------------------------------------------------------ | ------------------------------------------ |
| [`CLAUDE.md`](../../CLAUDE.md)                               | Regras de projeto                          |
| [`docs/devops/infra.md`](infra.md)                           | Infra DDEV (URLs, serviços, Vite, Horizon) |
| [`docs/devops/conventions.md`](conventions.md)               | Padrões de código, commits, review         |
| [`docs/devops/tools-and-packages.md`](tools-and-packages.md) | Ferramentas e pacotes                      |

---

## 9. FAQ rápido

**P: Posso rodar sem Docker?**
R: Não suportado. O DDEV (sobre OrbStack/Docker) é a via oficial — garante PHP 8.4, PostgreSQL 16 e Redis idênticos para todo o time.

**P: Posso mudar a URL/nome do projeto?**
R: Sim, editando `name:` em `.ddev/config.yaml` (e `APP_URL` no `.env`), depois `ddev restart`. O `bin/init-project.sh` faz isso a partir do slug ao iniciar um projeto novo.

**P: Como reseto TUDO?**
R: `ddev delete -O && ddev start && make setup`. O `delete -O` apaga volumes — **cuidado com dados locais**.

**P: Horizon e Pulse aparecem em branco.**
R: Em dev/local não tem auth; em staging/prod o gate `auth:admin` bloqueia se você não estiver logado. Faça login em `/admin/login` primeiro.

**P: Mudei `config/auth.php`, por que continua com comportamento antigo?**
R: `ddev artisan config:clear`. Em dev nunca rode `config:cache`.

**P: Meus testes ficam muito lentos.**
R: `ddev artisan test --parallel --processes=4`.

---

Última atualização: 2026-06-02 · Responsável por manter: time DevOps.
