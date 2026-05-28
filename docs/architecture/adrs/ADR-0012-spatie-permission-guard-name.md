---
title: 'ADR-0012: Spatie Permission com guard_name explícito por modelo'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0012: Spatie Permission com `guard_name` explícito por modelo

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Segurança | **Tags:** auth, acl, spatie

## Contexto e problema

O sistema tem múltiplos guards (`web` legado, `admin` para Blade/Livewire, `sanctum` para API v1 consumindo `PortalUser`, `convite` para token público). Cada guard tem sua própria tabela de usuários (ou provider). `spatie/laravel-permission` por default assume o guard `web`; se um modelo autenticável em outro guard (ex.: `PortalUser` em `sanctum`) chamar `$user->hasRole('comissao')`, a checagem vai contra o guard `web` e **falha silenciosamente** — sempre retorna `false`, abrindo brecha de autorização.

## Drivers da decisão

- Segurança: `hasRole()`/`can()` não podem falhar silenciosamente.
- Clareza: cada modelo autenticável é vinculado a um guard específico.
- Roles/permissions são compartilhados entre admin (Blade) e API (Sanctum) quando a regra é a mesma.

## Alternativas consideradas

### Alt 1: Ignorar e usar só `web` (default)

- Prós: zero configuração.
- Contras: falha de autorização silenciosa no guard `sanctum`; brecha de segurança.

### Alt 2: Criar role/permission separadas por guard

- Prós: isolamento total.
- Contras: duplicação massiva; manutenção insuportável; "role admin" teria que existir 3×.

### Alt 3: `guard_name` explícito por modelo + `guard_names` liberados em config (escolhida)

- Prós: cada modelo diz a qual guard pertence; roles são únicas mas válidas no guard correto; Spatie checa corretamente.
- Contras: exige disciplina — qualquer modelo novo autenticável deve declarar `$guard_name`.

## Decisão

Todo modelo autenticável que usa `HasRoles` declara `$guard_name` explicitamente:

```php
// App\Models\Acesso\PortalUser
protected string $guard_name = 'sanctum';

// App\Models\Acesso\AdminUser
protected string $guard_name = 'admin';
```

`config/permission.php` libera os guards válidos:

```php
'guard_names' => ['web', 'admin', 'sanctum'],
```

Regras adicionais:

1. **Comissão nunca herda admin** (§6.5). Role Spatie `comissao` ganha permissions explícitas (`comissao.convites.view`, `comissao.rsvp.view`, `comissao.enquetes.manage`). Jamais atribuir `admin.*` a `comissao`.
2. **Scope por evento em policies**: checagem `user->eventosAutorizados()->contains($evento->id)` para comissão.
3. **Cache de permissions** invalidado via `PermissaoAlterada` event (§9.3) — Spatie Permission **não** dispara esse evento automaticamente; usa-se Observer em `Role` + `Permission` models + pivot listeners.
4. Em login mobile (ADR-0003), abilities do Sanctum são derivadas de `getAllPermissions()->pluck('name')`.

Arch test Pest garante que `App\Models\Acesso\*` que usa `HasRoles` declara `$guard_name`.

## Consequências positivas

- `hasRole()`/`can()` funciona corretamente em todos os guards.
- Roles compartilhadas entre admin e API sem duplicação.
- Abilities Sanctum alinhadas com permissions Spatie (um único ponto de verdade).
- Bloqueio de escalação: comissão ≠ admin, validado por middleware + policy.

## Consequências negativas

- Cache de permissions precisa de invalidação explícita via Observer (Spatie não dispara evento nativo para attach/detach de pivot). Documentado em §9.3.
- Adicionar guard novo exige atualizar `guard_names` em config. Aceito.

## Ligações

- §6.1, §6.5 do PLANEJAMENTO_BACKEND_APIV1.md
- §9.3 (cache permissions + Observer)
- ADR-0003 (Sanctum), ADR-0011 (Horizon p/ listener queued)
- SAD arc42 seção "Segurança e autorização"
