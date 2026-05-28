---
title: 'ADR-0003: Laravel Sanctum em modo dual (SPA stateful + token mobile)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0003: Laravel Sanctum em modo dual (SPA stateful + token mobile)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Segurança | **Tags:** auth, sanctum, spa, mobile

## Contexto e problema

A API v1 serve, no mínimo, três tipos de cliente autenticado do formando: SPA React em domínio próprio (cookie-based), app mobile React Native (token Bearer persistido no dispositivo) e integrações internas/admin (token). O admin mantém sessão `web` via `admin` guard (Blade/Livewire). Como garantir um único guard na API com dois modos de apresentação do credencial, mantendo CSRF seguro para SPA e resiliência para mobile?

## Drivers da decisão

- SPA em subdomínio próprio precisa de cookie HttpOnly + CSRF token padrão Laravel.
- Mobile em React Native não participa do fluxo de cookies; precisa token Bearer persistido em keychain seguro.
- Admin (Blade) já possui guard `admin` isolado — não deve colidir com portal.
- Rota pública de convite (`/convite/{token}`) é um fluxo anônimo separado, não autenticado via Sanctum.
- Evitar dependência de OAuth2/Passport (over-engineering para MVP).

## Alternativas consideradas

### Alt 1: Laravel Passport (OAuth2)

- Prós: padrão OAuth2, grants variados.
- Contras: complexidade alta para o cenário (MVP interno); exige UI de authorize server; manutenção do grants/scopes custosa; tokens JWT rotativos pedem infra extra.

### Alt 2: JWT custom (tymon/jwt-auth)

- Prós: stateless; popular.
- Contras: invalidação de token é problemática sem blacklist; fora do runway oficial do Laravel; conflita com CSRF de SPA.

### Alt 3: Sanctum SPA-only (sem token)

- Prós: simplicidade.
- Contras: mobile fica impossibilitado; gambiarra com sessão headless não funciona em apps distribuídos.

### Alt 4: Sanctum dual-mode SPA + token (escolhida)

- Prós: oficial Laravel, simples, CSRF automatizado no SPA (cookie + `XSRF-TOKEN`), `personal_access_tokens` para mobile com abilities granulares; suporta revogação de token individual; um único guard `sanctum` resolve os dois modos.
- Contras: modelo de domínio `PortalUser` precisa `HasApiTokens` e `guard_name = 'sanctum'` quando combinado com Spatie Permission (ADR-0012).

## Decisão

Usar Laravel Sanctum com dois modos orquestrados pelo `LoginController`:

- **SPA (`mode=spa`)**: fluxo `GET /sanctum/csrf-cookie` → `POST /api/v1/auth/login` → `Auth::guard('portal')->attempt()` → `session()->regenerate()`. Cookie HttpOnly + Secure + SameSite=lax. `.env` define `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`.
- **Token (`mode=token`)**: `device_name` obrigatório no FormRequest → `user->createToken($deviceName, $abilities)` onde `abilities` deriva de `getAllPermissions()` Spatie. Resposta traz `access_token` (plain text, apenas uma vez) + `abilities`.
- **Middleware `auth:sanctum`** resolve ambos transparentemente.
- Guard `admin` (para Blade) e `convite` (custom token de convite) ficam separados.

## Consequências positivas

- Um único middleware (`auth:sanctum`) para todos os endpoints autenticados da API v1.
- Revogação de tokens mobile individual (logout de um device).
- Abilities Sanctum alinhadas com permissions Spatie (ADR-0012) dão granularidade.
- CSRF de SPA resolvido pelo Laravel padrão; zero código custom.

## Consequências negativas

- Requer cuidado com `EnsureFrontendRequestsAreStateful` no grupo `api` (habilitado via `$middleware->statefulApi()`). Sem isso, `$request->session()` é nulo e SPA quebra silenciosamente.
- Tokens mobile persistidos no banco (`personal_access_tokens`). Mitigação: expiração configurada + rotação periódica.

## Ligações

- §6.1 e §6.2 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0012 (Spatie Permission guard_name), ADR-0001 (API v1)
- SAD arc42 seção "Conceitos de corte transversal — Segurança"
