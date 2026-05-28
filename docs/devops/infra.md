# Infraestrutura Local — Portal ArtFinal

**Stack:** Docker (Laradock) · PHP 8.4 · PostgreSQL 16 · Redis · Nginx · Horizon · Pulse · pgAdmin · Mailpit

---

## URLs e Serviços

| Serviço           | URL                      | Porta | Container       |
| ----------------- | ------------------------ | ----- | --------------- |
| Aplicação Laravel | http://localhost         | 80    | nginx + php-fpm |
| Laravel Horizon   | http://localhost/horizon | 80    | laravel-horizon |
| Laravel Pulse     | http://localhost/pulse   | 80    | php-fpm         |
| pgAdmin           | http://localhost:5050    | 5050  | pgadmin         |
| Mailpit           | http://localhost:8125    | 8125  | mailpit         |
| PostgreSQL        | postgres:5432 (interno)  | 5432  | postgres        |
| Redis             | redis:6379 (interno)     | 6379  | redis           |

Smoke test de todas as URLs:

```bash
for u in http://localhost http://localhost/horizon http://localhost/pulse http://localhost:5050 http://localhost:8125; do
  printf "%-28s " "$u"; curl -s -o /dev/null -w "%{http_code}\n" "$u"
done
```

Esperado: `200` (ou `302` redirect em pgAdmin).

---

## Comandos Makefile

| Comando               | Descrição                             |
| --------------------- | ------------------------------------- |
| `make up`             | Sobe todos os containers              |
| `make down`           | Para todos os containers              |
| `make restart`        | Reinicia os containers                |
| `make build`          | Rebuilda os containers                |
| `make bash`           | Entra no workspace                    |
| `make artisan <cmd>`  | Roda `php artisan <cmd>` no workspace |
| `make composer <cmd>` | Roda `composer <cmd>` no workspace    |
| `make npm <cmd>`      | Roda `npm <cmd>` no workspace         |
| `make migrate`        | `php artisan migrate`                 |
| `make fresh`          | `php artisan migrate:fresh --seed`    |
| `make seed`           | `php artisan db:seed`                 |
| `make test`           | `php artisan test`                    |
| `make logs`           | `docker compose logs -f --tail=100`   |
| `make status`         | `docker compose ps`                   |
| `make setup`          | Roda `docker-setup.sh` do zero        |

---

## Primeiro Boot

```bash
# Na raiz do projeto
./docker-setup.sh
```

O script executa 6 passos:

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

## Patches locais aplicados no Laradock

O Laradock vendored em `laradock/` tem modificações locais necessárias
para rodar em Debian 13 (Trixie) com Redis 8 e Xdebug 3. Todas estão
documentadas em `laradock/PATCHES.md`:

| Patch | Arquivo(s)                                   | Motivo                                                                                                                                                              |
| ----- | -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1     | `php-fpm/Dockerfile`, `workspace/Dockerfile` | `INSTALL_PG_CLIENT` usando `apt-key add -` quebra no Debian 13 (`sqv` rejeita o keyring legado). Modernizado para `gpg --dearmor` + `signed-by=`.                   |
| 2     | `docker-compose.yml`                         | `command: --requirepass ${REDIS_PASSWORD}` em string form falha quando REDIS_PASSWORD é vazio porque Redis 8 auto-injeta `--loadmodule`. Convertido para list form. |
| 3     | `php-fpm/xdebug.ini`                         | `xdebug.mode=debug,develop` fazia `/horizon` e `/pulse` retornarem 502 (worker SIGKILL após 30 s, 100% CPU). Mantido só `debug`.                                    |

Sempre que o Laradock for sincronizado com upstream, reaplicar os
patches do `PATCHES.md` antes de rebuild.

---

## Troubleshooting

### Containers não sobem

```bash
make down
make build
make up
make logs
```

### Permissão em `storage/`

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

Para testar a partir do workspace:

```bash
docker compose exec workspace bash -c \
  "PGPASSWORD=secret psql -h postgres -U portalartfinal -d portalartfinal -c '\\l'"
```

### Redis fica em restart loop

Sintoma no log: `FATAL CONFIG FILE ERROR ... requirepass "--loadmodule"`.
Causa: `command:` do redis em string form no `docker-compose.yml` não
preserva REDIS_PASSWORD vazio. Fix: ver `laradock/PATCHES.md` Patch 2
(converter para list form).

### `/horizon` ou `/pulse` retornam 502

Sintoma: `/` responde 200 mas `/horizon` e `/pulse` caem em 502 após
~30 s. Log do php-fpm mostra `child N exited on signal 9 (SIGKILL)`
e `PHP Warning: Uncaught InvalidArgumentException: Driver [horizon]
not supported`.

Causa real: **não é** o `InvalidArgumentException` (esse é rescued pelo
`SentinelManager::driverOrFallback`). É o `xdebug.mode=develop` que
instrumenta cada chamada de função e trava o worker ao renderizar os
~1,7 MB de HTML do dashboard. Fix: ver `laradock/PATCHES.md` Patch 3
(usar só `xdebug.mode=debug` no FPM).

### Horizon não processa jobs

Após alterar `config/horizon.php`:

```bash
docker compose restart laravel-horizon
```

### Resetar banco e seeds

```bash
make fresh
```

### Rebuild dos containers após mudar Dockerfile

```bash
make down
docker compose build --no-cache workspace php-fpm
make up
```

---

## Referências

- `docs/01-ARCHITECTURE-GUIDE.md` — estrutura de pastas do projeto
- `docs/02-CONVENTIONS.md §9` — padrões de cache Redis
- `docs/02-CONVENTIONS.md §10` — filas do Horizon
- `CLAUDE.md §20` — resumo rápido do ambiente
- `laradock/PATCHES.md` — patches locais detalhados
