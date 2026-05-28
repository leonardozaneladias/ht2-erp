# Laradock — Local Patches (Portal ArtFinal)

This file lists **all local modifications** made on top of vendored
Laradock. Re-apply these whenever you sync with upstream `laradock/master`.

Upstream base commit: **`bff68251 Merge pull request #3667 from erikn69/patch-38`** (2026-02-02)

---

## Patch 1 — Modernize `INSTALL_PG_CLIENT` (Debian 13 / Trixie compatibility)

**Why:** Debian 13 (Trixie) replaced `gpg`/`gpgv` with `sqv` as apt's
signature verification tool. `sqv` rejects the legacy
`/etc/apt/trusted.gpg` keyring that `apt-key add -` populates, which
makes `apt.postgresql.org/pub/repos/apt $VERSION_CODENAME-pgdg main`
unsigned from apt's perspective and causes **every subsequent
`apt-get update` in the build** to fail with:

```
Err:2 http://apt.postgresql.org/pub/repos/apt trixie-pgdg InRelease
  Sub-process /usr/bin/sqv returned an error code (1), error message is:
  Missing key B97B0AFCAA1A47F044F244A07FCC7D46ACCC4CF8, ...
```

(The first step to fail is usually the imagemagick install, since it
runs `apt-get update` after `INSTALL_PG_CLIENT` has dropped `pgdg.list`
into `/etc/apt/sources.list.d/` and then purged `gnupg`.)

**Root cause references:**
- https://github.com/dalibo/temboard/issues/1617
- https://github.com/go-gitea/gitea/issues/35588

**Pattern:** Replace `apt-key add -` with Debian's modern
`gpg --dearmor` + `signed-by=` pattern (same convention Laradock
adopted in PR #3675 for ClickHouse).

### Files patched

1. `laradock/php-fpm/Dockerfile` — lines 206-217 (the
   `INSTALL_PG_CLIENT` block)
2. `laradock/workspace/Dockerfile` — lines 1355-1362 (the
   `INSTALL_PG_CLIENT` block)

### php-fpm/Dockerfile — diff

```diff
 RUN if [ ${INSTALL_PG_CLIENT} = true ]; then \
-    apt-get install -yqq gnupg \
+    apt-get install -yqq gnupg ca-certificates \
     && . /etc/os-release \
-    && echo "deb http://apt.postgresql.org/pub/repos/apt $VERSION_CODENAME-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
-    && curl -sL https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - \
+    && mkdir -p /usr/share/keyrings \
+    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/pgdg.gpg \
+    && echo "deb [signed-by=/usr/share/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt $VERSION_CODENAME-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
     && apt-get update -yqq \
     && apt-get install -yqq postgresql-client-${PG_CLIENT_VERSION} postgis; \
```

### workspace/Dockerfile — diff

```diff
 RUN if [ ${INSTALL_PG_CLIENT} = true ]; then \
     # Install the pgsql client
-    apt-get -yqq install wget \
-    && wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - \
-    && echo "deb http://apt.postgresql.org/pub/repos/apt/ `lsb_release -cs`-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list \
+    apt-get -yqq install wget gnupg ca-certificates \
+    && mkdir -p /usr/share/keyrings \
+    && wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/pgdg.gpg \
+    && echo "deb [signed-by=/usr/share/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt/ `lsb_release -cs`-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list \
     && apt-get update \
     && apt-get -y install postgresql-client-${PG_CLIENT_VERSION} \
 ;fi
```

### Removal condition

Remove this patch once Laradock upstream ships a compatible fix
(watch `laradock/laradock` for a PR touching `INSTALL_PG_CLIENT` in
either Dockerfile).

---

## Patch 2 — Quote `REDIS_PASSWORD` so empty value doesn't eat the next CLI arg

**Why:** `redis:latest` (Redis 8.6.x) now ships the "Redis Stack" modules
(`redisbloom.so`, `redisearch.so`, `redistimeseries.so`, `rejson.so`)
pre-installed in `/usr/local/lib/redis/modules/`. The upstream image's
entrypoint auto-appends `--loadmodule /usr/local/lib/redis/modules/...`
to the final `redis-server` invocation to enable them.

Laradock's docker-compose service still uses the historical string form:

```yaml
command: --requirepass ${REDIS_PASSWORD}
```

With `REDIS_PASSWORD=` (the Laradock default, which Portal ArtFinal keeps)
this shell-expands to `--requirepass` followed immediately by the
auto-appended `--loadmodule <path>`. Redis 8's argparser then consumes
`--loadmodule` as the **value** of `--requirepass`, the module path as the
next directive token, and produces:

```
*** FATAL CONFIG FILE ERROR (Redis 8.6.2) ***
Reading the configuration file, at line 2
>>> 'requirepass "--loadmodule" "/usr/local/lib/redis/modules//redisbloom.so"'
wrong number of arguments
```

The container enters a restart loop.

**Root cause:** `command:` string form does not preserve an empty argument
when the expanded variable is empty. The list form does.

### Files patched

1. `laradock/docker-compose.yml` — line 823 (the `redis:` service `command`)

### docker-compose.yml — diff

```diff
     redis:
       restart: always
       build: ./redis
       volumes:
         - ${DATA_PATH_HOST}/redis:/data
-      command: --requirepass ${REDIS_PASSWORD}
+      command: ["redis-server", "--requirepass", "${REDIS_PASSWORD}"]
       ports:
         - "${REDIS_PORT}:6379"
```

Array form preserves the empty string as a distinct argument, so
`redis-server` sees `--requirepass "" --loadmodule /path/...` and parses
the empty password correctly. The modules still load. The container
reports `Ready to accept connections tcp`.

### Removal condition

Remove this patch either:
- once Laradock upstream switches the `redis:` command to list form, OR
- once Portal ArtFinal sets a non-empty `REDIS_PASSWORD` in `laradock/.env`
  (which would also need a matching `REDIS_PASSWORD=` in Laravel's `.env`).

---

## Patch 3 — Drop `develop` from `xdebug.mode` in `php-fpm/xdebug.ini`

**Why:** Portal ArtFinal's Laradock xdebug.ini ships with:

```ini
xdebug.mode=debug,develop
xdebug.start_with_request=trigger
```

`trigger` only gates the `debug` mode (step debugger) — it does NOT
gate `develop`, which is a persistent instrumentation mode that adds
per-function-call overhead (improved var_dump, error context, stack
traces).

On a page that renders a lot of HTML through deep call stacks —
Horizon's `/horizon` and Pulse's `/pulse` dashboards render ~1.7 MB
of HTML each — the `develop` overhead turns a ~50 ms response into a
>30 s hang pegging one FPM worker at 100% CPU with >1 GB RAM until
Docker Desktop's OOM killer sends SIGKILL. Nginx sees "Connection
reset by peer" and returns **502** to the browser.

Repro: hit `http://localhost/horizon` with xdebug.mode=debug,develop →
502 after ~30 s, php-fpm log shows `child N exited on signal 9
(SIGKILL)`. Switch to `xdebug.mode=debug` → HTTP 200 in ~50 ms.

### Files patched

1. `laradock/php-fpm/xdebug.ini` — line 6 (xdebug.mode)

### xdebug.ini — diff

```diff
 ; Xdebug 3 configuration for PHP-FPM
 ; mode=debug: step debugging with breakpoints
-; mode=develop: improved var_dump, stack traces
+; mode=develop: improved var_dump, stack traces — DO NOT ENABLE IN FPM,
+;               it instruments every function call and kills pages that
+;               render lots of HTML (e.g. Horizon/Pulse dashboards).
+;               Enable only in workspace CLI or with XDEBUG_MODE=develop.
 ; mode=coverage: code coverage for PHPUnit
 ; Combine with commas: debug,develop,coverage
-xdebug.mode=debug,develop
+xdebug.mode=debug
 xdebug.start_with_request=trigger
```

**Workspace CLI still has `develop`:** the workspace container has its
own `laradock/workspace/xdebug.ini` — this patch does NOT touch it, so
artisan tinker and CLI var_dump retain pretty-printing. Only the FPM
request path loses `develop`.

### Removal condition

Never remove — this is a Portal ArtFinal config choice, not an upstream
Laradock bug. Future Laradock syncs should preserve `xdebug.mode=debug`
in `php-fpm/xdebug.ini`.

---
