# Infraestrutura Local

**Ambiente oficial:** [DDEV](https://docs.ddev.com/) · provider **OrbStack** (macOS).
**Stack:** PHP 8.4 · PostgreSQL 16 · Redis · Nginx · Horizon · Pulse · Mailpit · Vite 7.

A configuração vive versionada em `.ddev/` — qualquer pessoa que clone obtém o
mesmo ambiente. Em Windows/Linux o mesmo `.ddev/` roda com Docker Desktop/WSL2;
OrbStack é a recomendação apenas no macOS.

---

## Pré-requisitos (uma vez por máquina)

```bash
brew install orbstack          # abra o app 1x e selecione "Docker"
brew install ddev/ddev/ddev
```

OrbStack assume o contexto Docker automaticamente (não precisa de `docker context use`).
`performance_mode` fica no default do macOS (Mutagen) — OrbStack + Mutagen é a
combinação mais rápida nos benchmarks oficiais.

---

## Primeiro boot

```bash
cp .env.example .env
ddev start       # sobe containers; hooks rodam composer install, migrate, npm install
make setup       # 1x: key:generate, migrate --seed, assets Horizon/Pulse, npm build
ddev launch      # abre https://ht2ml-platform.ddev.site
```

O `ddev start` (project type `laravel`) reescreve as credenciais de banco no `.env`
(`DB_HOST=db`, db/usuário/senha = `db`). O serviço Redis (`.ddev/docker-compose.redis.yaml`)
sobe junto e fica acessível em `redis:6379`. Horizon roda como daemon persistente
(`web_extra_daemons` no `.ddev/config.yaml`).

---

## URLs e serviços

| Serviço           | URL / Comando                                       |
| ----------------- | --------------------------------------------------- |
| Aplicação Laravel | https://ht2ml-platform.ddev.site                           |
| Laravel Horizon   | https://ht2ml-platform.ddev.site/horizon                   |
| Laravel Pulse     | https://ht2ml-platform.ddev.site/pulse                     |
| Mailpit (UI)      | `ddev mailpit`                                      |
| Vite dev server   | https://ht2ml-platform.ddev.site:5173 (`ddev npm run dev`) |
| PostgreSQL        | `ddev psql` · host interno `db:5432`                |
| Redis             | host interno `redis:6379`                           |

Smoke test das URLs principais:

```bash
for u in https://ht2ml-platform.ddev.site https://ht2ml-platform.ddev.site/horizon https://ht2ml-platform.ddev.site/pulse; do
  printf "%-40s " "$u"; curl -sk -o /dev/null -w "%{http_code}\n" "$u"
done
```

Esperado: `200` (ou `302` para rotas autenticadas).

---

## Comandos Makefile

| Comando               | Descrição                                  |
| --------------------- | ------------------------------------------ |
| `make up`             | `ddev start`                               |
| `make down`           | `ddev stop`                                |
| `make restart`        | `ddev restart`                             |
| `make bash`           | Shell no container web (`ddev ssh`)        |
| `make artisan <cmd>`  | `ddev artisan <cmd>`                       |
| `make composer <cmd>` | `ddev composer <cmd>`                      |
| `make npm <cmd>`      | `ddev npm <cmd>`                           |
| `make dev`            | Vite dev server (`ddev npm run dev`)       |
| `make migrate`        | `ddev artisan migrate`                     |
| `make fresh`          | `migrate:fresh --seed` + DevelopmentSeeder |
| `make seed`           | `ddev artisan db:seed`                     |
| `make horizon`        | Reinicia o daemon Horizon (supervisorctl)  |
| `make test`           | `ddev artisan test`                        |
| `make lint`           | Pint + Prettier                            |
| `make quality`        | Lint + PHPStan + Test                      |
| `make logs`           | `ddev logs -f`                             |
| `make status`         | `ddev describe`                            |
| `make setup`          | Setup inicial (key, seed, assets, build)   |

---

## Acessos

**PostgreSQL** — host interno `db`, porta `5432`, database/usuário/senha = `db`
(geridos pelo DDEV). Conexão direta: `ddev psql` ou `ddev describe` (mostra a
porta exposta no host).

**Redis** — host interno `redis`, porta `6379`, sem senha. Definido em
`.ddev/docker-compose.redis.yaml`. Usado por `CACHE_STORE`, `SESSION_DRIVER`,
`QUEUE_CONNECTION` e `BROADCAST_CONNECTION` (todos `redis` no `.env`).

**Mailpit** — embutido no DDEV. SMTP `localhost:1025` (a partir do container web),
UI via `ddev mailpit`.

---

## Vite + HMR

O dev server é exposto via `web_extra_exposed_ports` no `.ddev/config.yaml` e
configurado em `vite.config.js` (bloco `server` com `origin` baseado em
`DDEV_PRIMARY_URL_WITHOUT_PORT` e `cors` para `*.ddev.site`).

```bash
ddev npm run dev      # assets em https://ht2ml-platform.ddev.site:5173, com hot-reload
ddev npm run build    # build de produção
```

---

## Horizon (daemon)

Horizon roda como `web_extra_daemons` dentro do container web (supervisord interno):

```bash
ddev exec supervisorctl status                       # lista webextradaemons:horizon
ddev exec supervisorctl restart webextradaemons:horizon   # = make horizon
```

Requer `laravel/horizon` instalado (o hook `composer install` garante isso antes
do daemon subir).

---

## Troubleshooting

### `ddev start` falha

```bash
ddev logs            # logs do container web
ddev poweroff && ddev start
```

### Horizon não processa jobs / config alterada

```bash
make horizon         # reinicia o daemon após mudar config/horizon.php
```

### `/horizon` ou `/pulse` retornam erro

Confirme que Redis está no ar e que `.env` usa `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`,
`CACHE_STORE=redis`. `ddev exec php artisan tinker --execute="echo config('database.redis.default.host');"`
deve imprimir `redis`.

### Resetar banco e seeds

```bash
make fresh
```

### Recriar o ambiente do zero

```bash
ddev delete -O      # remove containers/volumes do projeto (mantém o código)
ddev start && make setup
```

---

## Referências

- `docs/01-ARCHITECTURE-GUIDE.md` — estrutura de pastas do projeto
- `docs/02-CONVENTIONS.md §9` — padrões de cache Redis
- `docs/02-CONVENTIONS.md §10` — filas do Horizon
- `CLAUDE.md §15` — resumo rápido do ambiente
- [docs.ddev.com](https://docs.ddev.com/) — documentação oficial do DDEV
