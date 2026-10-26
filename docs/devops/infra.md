# Infraestrutura Local

**Ambiente oficial:** [DDEV](https://docs.ddev.com/) · provider **OrbStack** (macOS).
**Stack:** PHP 8.4 · PostgreSQL 16 · Redis · Nginx · Horizon · Pulse · Mailpit · Vite 8 (`^8.2` no `package.json`) · Node 22.

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
O `.ddev/config.yaml` deste repositório fixa `performance_mode: none` (bind mounts
diretos, sem Mutagen): o Mutagen falha com `permission denied` quando o diretório
pai do projeto não é gravável, como em `/Users/Shared`. Numa pasta do seu usuário,
dá para remover a linha e voltar ao default do macOS (Mutagen). Ver
[`ddev-setup.md` §4](ddev-setup.md#4-o-que-fica-versionado-em-ddev).

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
| `make fresh`          | `ddev artisan migrate:fresh --seed`        |
| `make seed`           | `ddev artisan db:seed`                     |
| `make horizon`        | Reinicia o daemon Horizon (supervisorctl)  |
| `make test`           | `ddev artisan test --parallel --testsuite=Unit,Feature,Extensoes,Arch` |
| `make test-e2e`       | `npm run build` + `pest --group=browser` (no host, fora do container) |
| `make lint`           | Pint + Prettier                            |
| `make quality`        | `ddev npm run quality` (Pint + ESLint + Prettier + PHPStan + testes) |
| `make doutor`         | `ddev exec php artisan ht2ml:doutor`       |
| `make logs`           | `ddev logs -f`                             |
| `make status`         | `ddev describe`                            |
| `make setup`          | Setup inicial (key, seed, assets, build)   |
| `make setup-client`   | Setup de instância de cliente: sem dados demo, só `RolePermissionSeeder`; o Setup Wizard faz o resto |
| `make new-client`     | `./bin/new-client.sh` (no host)            |
| `make release-modulo` | `./bin/release-module.sh $(pacote) $(versao)` — ex.: `make release-modulo pacote=core versao=v0.1.0` |
| `make update-base`    | `./bin/update-from-upstream.sh` (no cliente) |

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

- [`../architecture/index.md`](../architecture/index.md) e [`CONTEXT-MAP.md`](../../CONTEXT-MAP.md) — arquitetura e organização do monorepo
- [`conventions.md` §9](conventions.md#9-cache-redis) — padrões de cache Redis
- [`conventions.md` §10](conventions.md#10-filas-horizon) — filas do Horizon
- [`CLAUDE.md`](../../CLAUDE.md), seção "Ambiente de Desenvolvimento" — resumo rápido do ambiente
- [docs.ddev.com](https://docs.ddev.com/) — documentação oficial do DDEV
