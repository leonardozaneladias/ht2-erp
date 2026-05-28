---
title: 'ADR-008: Sanctum stateful (cookie) no SPA web; token para mobile em F8'
adr: 008
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - auth
    - sanctum
    - csrf
    - cookie
    - seguranca
---

# ADR-008: Sanctum stateful (cookie) no SPA web; token para mobile em F8

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, auth, sanctum, csrf, cookie, seguranca

## 1. Contexto

O backend já decidiu o esquema de auth em **ADR-0003 backend (Sanctum dual-mode)**: Sanctum resolve SPA via cookie stateful + mobile via personal access token, atrás de um único `auth:sanctum` middleware. A decisão backend é **fonte**; esta ADR é a **consequência do lado do cliente**: como o SPA React consome corretamente esse esquema.

Pergunta: no cliente SPA, **como armazenar credencial**? Alternativas clássicas:

1. **JWT em `localStorage`** — Bearer token enviado em `Authorization` header.
2. **JWT em cookie HttpOnly** — equivalente a session, mas com JWT estruturado.
3. **Sanctum stateful cookie** — cookie de sessão Laravel padrão + CSRF via `XSRF-TOKEN`.
4. **OAuth2 authorization code flow** — PKCE para SPAs.

Cada escolha tem implicações para:

- Proteção contra XSS.
- Proteção contra CSRF.
- Reuso no mobile F8.
- Complexidade de implementação.

## 2. Decisão

**O SPA React consome a API v1 em modo Sanctum stateful via cookie de sessão Laravel.** Nenhum token é manipulado pelo JavaScript do SPA. O mobile F8 usa o outro modo do Sanctum (personal access tokens Bearer), conforme ADR-0003 do backend.

Detalhes operacionais no SPA web:

- **Axios config**: `withCredentials: true` em todas as requests. Isso faz o browser enviar/receber cookies `XSRF-TOKEN` e `laravel_session`.
- **Interceptor CSRF**: antes de **toda mutação** (`POST`/`PUT`/`PATCH`/`DELETE`), o interceptor chama `GET /sanctum/csrf-cookie` se ainda não há cookie `XSRF-TOKEN` válido. Axios, por convenção, lê `XSRF-TOKEN` automaticamente e envia como header `X-XSRF-TOKEN`.
- **Login**: `POST /api/v1/auth/login` com `{ cpf, senha }`. Backend valida, chama `Auth::guard('portal')->attempt(...)`, `$request->session()->regenerate()`. Resposta `200 { data: { formando } }`. Cookie `laravel_session` é atualizado.
- **Sessão**: estado "estou logado?" é derivado de `authStore.user !== null`. O SPA confia no servidor via `GET /api/v1/auth/me` no boot e após navegações sensíveis.
- **Logout**: `POST /api/v1/auth/logout` → backend invalida sessão → limpa cookie. SPA chama `authStore.logout()` e navega para `/login`.
- **401 interceptor**: qualquer response 401 dispara `authStore.logout()` e redirect para `/login?redirect=<current>`.
- **Domínio**: em produção, SPA e API no mesmo apex domain (ex.: `portal.artfinal.com.br` e `api.artfinal.com.br` → `SANCTUM_STATEFUL_DOMAINS=*.artfinal.com.br`, `SESSION_DOMAIN=.artfinal.com.br`).

**Nada de token**:

- Nenhum JWT, nenhum Bearer, nenhum `localStorage.setItem('token', ...)`.
- RSVP público (rota `/rsvp/$token`) usa token **no path** (parte da URL) e **não** é credencial de sessão — é identificador do convite (ADR-0013 backend trata como natural unique key).

Mobile F8 (fora do escopo direto desta ADR mas ligado):

- `POST /api/v1/auth/login` com `device_name` → retorna `{ access_token, abilities }`.
- App armazena `access_token` em `expo-secure-store` (keychain iOS / keystore Android).
- Axios config do app usa `Authorization: Bearer <token>` + `withCredentials: false`.

## 3. Consequências

### Positivas

- **XSS não rouba credencial**: cookie `laravel_session` é `HttpOnly`; JavaScript do SPA não consegue ler nem vazar. Se um ataque XSS acontece, o atacante pode executar requests no contexto do usuário, mas **não** exfiltrar o token para uso offline posterior.
- **CSRF resolvido**: padrão Laravel Sanctum (cookie `XSRF-TOKEN` + header `X-XSRF-TOKEN`) é auditado e robusto. Axios faz isso automático com `withCredentials: true`.
- **Zero custo de auth**: SPA não precisa gerenciar refresh token, expiração, rotação. Sessão Laravel cuida (TTL default 2 h, configurável).
- **Alinhamento total com ADR-0003 backend**: um único `auth:sanctum` middleware cobre SPA e mobile; zero lógica custom de autenticação.
- **Simplicidade do `authStore`**: guarda apenas `{ user, isAuthenticated }`, derivados de `/me`. Nenhum token, nenhum expiry check.
- **Desenvolvimento local trivial**: Vite proxy para `/api` e `/sanctum` bypassa CORS; cookies fluem naturalmente.

### Negativas

- **Cookie-based auth exige cuidado com CORS/CSRF config**: `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `cors.php` com `supports_credentials: true`. Se mal configurado, SPA "quebra silenciosamente" (status 419 ou 401 sem motivo claro). Mitigação: checklist pré-F3 documenta config exata.
- **Mesmo apex domain em produção**: não podemos colocar SPA em `spa.vercel.app` e API em `api.artfinal.com.br` facilmente — cookies cross-site têm restrições (SameSite=lax default do Chrome bloqueia). Mitigação: arquitetura assume SPA e API no mesmo apex.
- **Não funciona em iframes de domínio externo**: se alguém quisesse embarcar o SPA em iframe em outro site, SameSite bloqueia. Aceitável (design não prevê iframe externo).
- **Sessão Laravel é stateful**: scale horizontal do backend exige session driver compartilhado (Redis, que já temos).
- **Refresh silencioso ausente no MVP**: se a sessão expira durante uso, próxima request dá 401 → logout forçado. Aceitável para o MVP; mobile F8 pode adicionar refresh token se necessário.

## 4. Trade-offs

| Ganhamos                                                 | Perdemos                                                       |
| -------------------------------------------------------- | -------------------------------------------------------------- |
| XSS não rouba credencial (HttpOnly cookie)               | Config CORS/Sanctum sensível (checklist obrigatório)           |
| CSRF nativo do Laravel, zero código custom               | Mesmo apex domain em prod (restringe deploy exótico)           |
| Alinhamento com ADR-0003 backend (auth:sanctum único)    | Sessão Laravel exige Redis shared para scale horizontal        |
| `authStore` trivial (sem refresh, sem expiry management) | Sem refresh silencioso no MVP — 401 força logout               |
| Mobile F8 usa o mesmo middleware com token               | Dois fluxos de login (spa vs token) no LoginController backend |
| Dev local simples via Vite proxy                         | Cookie SameSite=lax impede iframe cross-origin                 |

## 5. Alternativas rejeitadas

### Alt 1: JWT em `localStorage` + `Authorization: Bearer`

- **Prós**: padrão na indústria para SPAs cross-domain; stateless; escala horizontalmente trivial.
- **Contras**:
    - **XSS = roubo total de credencial**: qualquer XSS lê `localStorage.token` e exfiltra. Atacante usa o token offline por todo o TTL. Este é o ataque mais comum e mais devastador em SPAs modernos.
    - **Não há proteção CSRF automática**: JWT em header não é enviado por `<form>` ou `<img>`, mas se o app fizer qualquer cookie-based complement, CSRF reaparece.
    - **Refresh token complexo**: rotação, blacklist, logout seguro requer infraestrutura.
    - **Contradiz ADR-0003 backend**: Sanctum dual-mode foi escolhido justamente para **não** usar JWT.

### Alt 2: JWT em cookie HttpOnly (sem Sanctum)

- **Prós**: HttpOnly resolve XSS; JWT é stateless.
- **Contras**:
    - **Custom**: Laravel Sanctum já resolve esse caso. Implementar cookies HttpOnly com JWT seria reinventar Sanctum stateful.
    - **CSRF ainda precisa ser resolvido** (token ou double-submit).
    - **Invalidação de JWT**: blacklist ou rotação — complexidade extra.

### Alt 3: Laravel Passport (OAuth2)

- **Prós**: padrão OAuth2 completo; grants variados.
- **Contras**:
    - **Over-engineering**: não temos cenário de third-party apps pedindo authorization de usuário.
    - **Complexidade de UI de authorize**, scopes, token rotation — tudo para uso interno desnecessário.
    - **ADR-0003 backend rejeitou Passport** explicitamente.

### Alt 4: Sanctum SPA-only (sem modo token)

- **Prós**: simplicidade máxima.
- **Contras**:
    - **Impossibilita mobile F8**: RN em app distribuído não tem ciclo de cookie web. Precisa token.
    - **ADR-0003 backend já decidiu dual-mode**.

### Alt 5: Cookie de sessão regular (não Sanctum)

- **Prós**: nativo Laravel.
- **Contras**:
    - **Sanctum adiciona automaticamente** a infra CSRF + SPA stateful + personal access tokens para mobile em um pacote coeso. Não usar Sanctum seria reconstruir isso à mão.

## 6. Status

**Accepted.** Fortemente congelado — consequência direta da ADR-0003 do backend.

Checklist operacional:

- [ ] `config/cors.php` publicado com `supports_credentials: true` e `allowed_origins: [env('FRONTEND_URL')]`.
- [ ] `config/sanctum.php` com `stateful` incluindo `localhost`, `localhost:5173` (dev) e apex domain (prod).
- [ ] `axios` instance com `withCredentials: true` — **não esquecer**.
- [ ] Interceptor CSRF: `GET /sanctum/csrf-cookie` antes de toda mutação.
- [ ] Interceptor 401: `authStore.logout()` + redirect `/login`.
- [ ] Nenhum uso de `localStorage` para auth. Inventário de storage limpo em F7.
- [ ] Vite proxy em dev para `/api` e `/sanctum` apontando para Laravel.
- [ ] Em produção, SPA e API **no mesmo apex domain** ou pelo menos com `SESSION_DOMAIN` compatível.

Revisão futura:

- Se surgir requisito de SSO federado (Google/Microsoft), abrir ADR nova para Socialite/Passport sobre o Sanctum.
- Se mobile F8 crescer e exigir rotação de tokens com refresh, abrir ADR de refresh-token para mobile (sem afetar o SPA web).

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §0 item 4, §3 (Axios client), §11 (backend prerequisites)
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §6.1, §6.2 (Sanctum dual-mode)
- `docs/architecture/adrs/ADR-0003-sanctum-dual-mode.md` — decisão backend espelhada aqui
- `docs/frontend/05-FRONTEND-SAD.md` §6.1 (login flow), §6.6 (401 interceptor), §8.5 (security), §11 R7
- ADR-001 (SPA React puro), ADR-002 (API v1), ADR-007 (sessionStorage, não localStorage para auth)
