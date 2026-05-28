---
title: SPEC-F-010 — Auth & Authorization base
version: 0.2.0
date: 2026-04-19
status: draft
feature_id: SPEC-F-010
fase: foundation
story_points: 3
depends_on: []
unlocks: [SPEC-001, SPEC-010]
---

# SPEC-F-010 — Auth & Authorization base

> **Fundacional.** Consolida a camada de autenticação (Sanctum) e autorização (Policies + Gates + Spatie Permission) que atravessa todos os SPECs. Hoje SPEC-001 cobre login user-facing, mas a infra compartilhada (guards, middlewares, policy base) vive espalhada em PLANEJAMENTO_BACKEND §2.4 e §6.

---

## 1. Escopo

### 1.1 Coberto

- Guards: `admin` (AdminUser), `portal` (PortalUser), `sanctum` (API)
- Sanctum stateful para web + token para mobile (futuro)
- Middlewares: `auth:sanctum`, `EnsureSanctumAbility`, `ResolveAdesaoContext` (ver SPEC-010), `ValidateDraftTokenBindings`, `RateLimitByActor`
- **Draft token JWT** (fluxo público SPEC-010): HS256, TTL 48h, segredo separado (`DRAFT_TOKEN_SECRET`). Claims: `sub: adesao_draft`, `contrato_ulid` (do código), `turma_ulid` (escolha curso+período — preenchido após step 1 do wizard público), `pacote_ulid` (escolha pacote formatura — preenchido após step 2), `tipo_solicitante`, `cpf_hash` (SHA-256 do CPF informado), `iat`, `exp`, `jti`. Revogação via Redis set `draft_token:revoked:{jti}`. Validação `X-Adesao-Draft-Token` em cada request do wizard público.
- Policies: `AdesaoPolicy`, `ConvitePolicy`, `FormandoAccessPolicy` (integração SPEC-F-003), `ContratoPolicy` (SPEC-F-001)
- Spatie Permission: roles `admin`, `comissao`, `formando`, `responsavel_financeiro` + permissions granulares

### 1.2 Fora do escopo

- Fluxos de UX de login/signup/reset (SPEC-001)
- Social login, 2FA (v2+)
- SSO empresarial (v2+)

---

## 2. Guards

```php
// config/auth.php
'guards' => [
    'admin'   => ['driver' => 'session', 'provider' => 'admin_users'],
    'portal'  => ['driver' => 'session', 'provider' => 'portal_users'],
    'sanctum' => ['driver' => 'sanctum',  'provider' => null],
    'convite' => ['driver' => 'convite', 'provider' => null],  // custom (token público)
],
```

Regra de ouro (CLAUDE.md §5.1): **nunca compartilhar session entre `admin` e `portal`**.

---

## 3. Abilities (Sanctum)

Abilities por token quando emitidos:

| Ability                 | Quem emite                       | Quando                                       |
| ----------------------- | -------------------------------- | -------------------------------------------- |
| `portal:read`           | Login portal                     | Após autenticação com credenciais            |
| `portal:write`          | Login portal                     | Após autenticação com credenciais            |
| `admin:full`            | Login admin                      | Após autenticação admin                      |
| `adesao:commit-publico` | `PublicoContext` draft_token     | Temporário durante wizard público (SPEC-010) |
| `convite:rsvp`          | Middleware `ResolveConviteToken` | Acesso via token de convite                  |

Middleware `EnsureSanctumAbility` valida ability por rota.

---

## 4. Policies base

### 4.1 `FormandoAccessPolicy` (consumido por várias specs)

```php
public function view(PortalUser $user, Formando $formando): bool
{
    return $user->formandos()->whereKey($formando->id)->exists();
}

public function updateProfile(PortalUser $user, Formando $formando): bool
{
    // só o próprio formando ou responsável vinculado
    return $this->view($user, $formando);
}
```

### 4.2 `ContratoPolicy`

```php
public function viewPublic(?User $user, Contrato $contrato): bool
{
    return $contrato->adesao_publica_ativa && $contrato->status === 'ativo';
}

public function manage(AdminUser $admin, Contrato $contrato): bool
{
    return $admin->hasRole('admin');
}
```

---

## 5. Roles e permissions (Spatie)

| Role                     | Permissions                                                       |
| ------------------------ | ----------------------------------------------------------------- |
| `admin`                  | `*` (admin:full)                                                  |
| `comissao`               | `turma:view`, `formandos:view`, `convites:manage`, `adesoes:view` |
| `formando`               | `adesao:own`, `pagamentos:own`, `convites:own`, `extras:buy`      |
| `responsavel_financeiro` | Mesmas do formando + `pagamentos:manage`                          |

---

## 6. Rate limiting por actor

```php
RateLimiter::for('actor', function (Request $request) {
    $user = $request->user();
    return $user
        ? Limit::perMinute(60)->by($user->id)
        : Limit::perMinute(10)->by($request->ip());
});
```

Rotas públicas (SPEC-010) têm limits mais restritivos em `RateLimiter::for('adesao-publica', ...)`.

---

## 7. Pontos a expandir na versão `draft`

- [ ] Estrutura detalhada de tokens Sanctum (TTL, revogação em logout, cascade em mudança de senha)
- [ ] Policy base class `BasePolicy` com helpers
- [ ] Middleware `ImpersonateUser` para suporte admin (com audit log)
- [ ] Recuperação de senha (flow via email; TTL do token)
- [ ] Bloqueio após N tentativas (5 tentativas → bloqueio 15min)
- [ ] Testes: policy matrix (role × recurso × ação), ability check em endpoints

---

---

## 9. Rate Limiters do SPEC-010

> Extraído do plano de implementação `2026-04-19-adesao-publica-codigo-contrato-plan.md` — Gate 1, Task 1.6. Provider registrado em `bootstrap/providers.php`.

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('adesao-publica-show', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('adesao-publica-iniciar', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('adesao-publica-simular', fn (Request $r) => Limit::perMinute(20)->by($r->ip()));
        RateLimiter::for('adesao-publica-commit', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
    }
}
```

**Tabela resumo dos limites:**

| Rate limiter             | Limite            | Endpoint associado                    |
| ------------------------ | ----------------- | ------------------------------------- |
| `adesao-publica-show`    | 10 req/min por IP | `GET /portal/adesao/publica/{codigo}` |
| `adesao-publica-iniciar` | 5 req/min por IP  | `POST /portal/adesao/publica/iniciar` |
| `adesao-publica-simular` | 20 req/min por IP | `POST /portal/adesao/publica/simular` |
| `adesao-publica-commit`  | 3 req/min por IP  | `POST /portal/adesao/publica/commit`  |

> Registro em `bootstrap/providers.php`:
>
> ```php
> return [
>     App\Providers\AppServiceProvider::class,
>     App\Providers\RateLimiterServiceProvider::class,
> ];
> ```

---

## 10. Middleware ResolveAdesaoContext (esboço)

> Criado no Gate 4 do plano de implementação. Descrito aqui como contrato de interface para que os SPECs dependentes (SPEC-010) possam referenciar o comportamento esperado.

**Caminho:** `app/Http/Middleware/ResolveAdesaoContext.php`

**Responsabilidade:** Decidir o contexto de execução de cada requisição aos endpoints públicos de adesão — usuário autenticado via Sanctum ou contexto anônimo via `draft_token`.

**Fluxo de resolução:**

```
Requisição chega
    │
    ├─ Header Authorization: Bearer <token> presente?
    │   └─ Sanctum verifica token → PortalUser autenticado
    │       ├─ Token válido  → continua com $request->user() = PortalUser
    │       └─ Token inválido → 401 Unauthorized
    │
    ├─ Header X-Adesao-Draft-Token: <jwt> presente?
    │   └─ Valida JWT HS256 (secret = DRAFT_TOKEN_SECRET)
    │       ├─ JWT válido + jti não revogado em Redis
    │       │   → injeta PublicoContext no request (anônimo, TTL 48h)
    │       │   → permite abilities: ['adesao:commit-publico']
    │       ├─ JWT expirado → 401 com mensagem "Sessão de adesão expirada"
    │       └─ jti revogado  → 401 com mensagem "Sessão de adesão inválida"
    │
    └─ Nenhum dos dois → 401 Unauthorized
```

**PublicoContext** é um Value Object injetado no request contendo:

- `draft_session_id` (jti do JWT)
- `contrato_ulid` (claim do JWT)
- `step_atual` (claim do JWT, validado pelo controller)
- `ttl_expira_em` (timestamp de expiração)

**Integração com Sanctum:** O middleware é aplicado **apenas** nas rotas públicas de adesão (`routes/portal.php`, grupo `/adesao/publica/*`). Rotas autenticadas normais continuam usando `auth:sanctum` diretamente.

**Requisito de segurança:** O `jti` do JWT deve ser armazenado em Redis com TTL igual ao do token (48h) ao emitir. Na revogação (logout, conclusão da adesão ou erro fatal), o jti é marcado como `revogado` no Redis antes de expirar naturalmente.

---

## 11. Referências

- [`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2, §6](../../prd/PLANEJAMENTO_BACKEND_APIV1.md) — base existente
- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §11 — conceito original
- [`SPEC-001`](../SPEC-001-login.md) — UX de login
- [`SPEC-010`](../SPEC-010-adesao-publica-codigo-contrato.md) — consumidor (ResolveAdesaoContext, ability `adesao:commit-publico`)
- [`docs/superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md`](../../superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md) Gate 1 Task 1.6, Gate 4

---

_**Estado:** `draft` (v0.2.0). Rate limiters e esboço do ResolveAdesaoContext adicionados. Pontos pendentes: estrutura de tokens Sanctum, BasePolicy, middleware Impersonate, fluxo de recuperação de senha._
