---
title: 'ADR-0012: Spatie Permission com guard_name explícito por modelo'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0012: Spatie Permission com `guard_name` explícito por modelo

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Segurança | **Tags:** auth, acl, spatie

## Contexto e problema

A autenticação do admin usa o guard `admin` (modelo `AdminUser`, tabela `admin_users`), distinto do guard `web` default do Laravel. `spatie/laravel-permission` por default assume o guard `web`; se um modelo autenticável em outro guard (ex.: `AdminUser` no guard `admin`) chamar `$user->hasRole('gestor')`, a checagem vai contra o guard `web` e **falha silenciosamente** — sempre retorna `false`, abrindo brecha de autorização.

Mesmo com um único guard de aplicação (`admin`), depender do default `web` é frágil: basta criar o modelo sem declarar o guard para a ACL parar de funcionar sem erro visível.

## Drivers da decisão

- Segurança: `hasRole()`/`can()` não podem falhar silenciosamente.
- Clareza: o modelo autenticável é vinculado explicitamente ao guard que o autentica.
- Consistência: roles/permissions são definidas no guard correto, não no default herdado por acaso.

## Alternativas consideradas

### Alt 1: Ignorar e usar só `web` (default)

- Prós: zero configuração.
- Contras: como o `AdminUser` autentica pelo guard `admin`, a checagem contra `web` falha silenciosamente; brecha de segurança.

### Alt 2: Forçar o `AdminUser` a autenticar pelo guard `web`

- Prós: alinha com o default do Spatie sem declarar nada.
- Contras: mistura o guard de aplicação com o default genérico; perde clareza e dificulta evoluir para outros guards no futuro.

### Alt 3: `guard_name` explícito por modelo + `guard_names` liberados em config (escolhida)

- Prós: o modelo diz a qual guard pertence; roles/permissions ficam válidas no guard correto; Spatie checa corretamente.
- Contras: exige disciplina — qualquer modelo novo autenticável deve declarar `$guard_name`.

## Decisão

Todo modelo autenticável que usa `HasRoles` declara `$guard_name` explicitamente:

```php
// App\Models\Acesso\AdminUser
protected string $guard_name = 'admin';
```

`config/permission.php` libera os guards válidos:

```php
'guard_names' => ['web', 'admin'],
```

Regras adicionais:

1. **Roles com permissions explícitas** — uma role recebe apenas as permissions que precisa (ex.: `gestor.usuarios.view`, `gestor.relatorios.export`). Jamais atribuir um curinga `*` para conceder tudo de uma vez.
2. **Cache de permissions** invalidado via evento `PermissaoAlterada` — Spatie Permission **não** dispara esse evento automaticamente; usa-se Observer em `Role` + `Permission` models + pivot listeners.

Arch test Pest garante que todo modelo em `App\Models\Acesso\*` que usa `HasRoles` declara `$guard_name`.

## Consequências positivas

- `hasRole()`/`can()` funciona corretamente no guard `admin`.
- A vinculação modelo → guard fica explícita e auditável.
- A configuração já está pronta para um eventual guard adicional sem retrabalho.

## Consequências negativas

- Cache de permissions precisa de invalidação explícita via Observer (Spatie não dispara evento nativo para attach/detach de pivot).
- Adicionar guard novo exige atualizar `guard_names` em config. Aceito.

## Ligações

- ADR-0011 (Horizon p/ listener queued de invalidação de cache)
