---
title: SPEC-001 — Autenticação (Login / Logout / Me)
version: 1.0.0
date: 2026-04-19
status: draft
feature_id: SPEC-001
fase: F3 (foundation — bloqueia todas as rotas protegidas)
story_points: 5
depends_on: []
unlocks: [SPEC-002, SPEC-003, SPEC-004, SPEC-006, SPEC-007, SPEC-008, SPEC-009]
---

# SPEC-001 — Autenticação (Login / Logout / Me)

> **Spec unificada backend + frontend.** Esta é a feature foundation: sem ela, nenhuma rota protegida do Portal do Formando existe.
> Fontes: [PLANEJAMENTO_BACKEND_APIV1.md §2.4](../prd/PLANEJAMENTO_BACKEND_APIV1.md) · [PLANEJAMENTO_FRONTEND_REACT.md §3,§6,§11](../prd/PLANEJAMENTO_FRONTEND_REACT.md) · [api-contract.md §1](../api/api-contract.md) · [ADR-008 Sanctum stateful](../frontend/06-ADR/ADR-008-sanctum-stateful-cookie-web.md)

---

## 0. Resumo executivo

O formando acessa `https://portal.artfinal.com.br/login`, informa e-mail + senha. O SPA chama `GET /sanctum/csrf-cookie`, depois `POST /api/v1/auth/login` (`mode: 'spa'`), recebe cookie `laravel_session` **HttpOnly**, dispara `GET /api/v1/me` e é redirecionado para `/portal/home`. Token **nunca** é tocado por JavaScript. Em `401` em qualquer rota protegida, o SPA limpa o store e volta para `/login?redirect=...`. Rate limit 5/min por `email+ip`. Mobile (F8) usa o mesmo endpoint com `mode: 'token'`.

---

## 1. Visão da feature

### 1.1 Jornada macro

```mermaid
flowchart LR
    A[/login] -->|informa email+senha| B{valida}
    B -->|ok| C[csrf-cookie + POST /auth/login]
    C -->|200 + cookie| D[GET /me]
    D -->|200| E[/portal/home]
    B -->|inválido| F[toast erro inline]
    C -->|401| F
    C -->|429| G[toast Retry-After]
    C -->|422| H[field errors inline]
```

### 1.2 Atores

| Ator                   | Ação                                                        |
| ---------------------- | ----------------------------------------------------------- |
| Formando               | Autentica para acessar portal (jornada primária).           |
| Responsável financeiro | Usa mesmas credenciais do formando (compartilha conta MVP). |
| Mobile F8 (futuro)     | Consome o mesmo endpoint com `mode: 'token'` → Bearer.      |
| Operação/comissão      | Fora de escopo desta spec (usam o admin Blade).             |

### 1.3 Valor

- Entrega a **porta de entrada** de todos os módulos protegidos.
- Garante que XSS não roube credenciais (cookie `HttpOnly`).
- Desacopla autenticação web vs mobile (dual-mode Sanctum — [ADR-0003 backend](../architecture/adrs/ADR-0003-sanctum-dual-mode.md)).

### 1.4 Escopo

**In:** login, logout, `GET /me`, guard de rotas, redirect pós-login (`?redirect=`), handle de 401 global.
**Out:** recuperação de senha (D7 em [14-OPEN-QUESTIONS](../frontend/14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md)), signup (convidado usa RSVP, não login), 2FA (pós-MVP), SSO (pós-MVP).

---

## 2. Contrato da API

### 2.1 `POST /api/v1/auth/login`

- **Route name:** `api.v1.auth.login`
- **Middlewares:** `throttle:login` (5/min por `email+ip`)
- **Auth:** nenhuma
- **Idempotência:** não exigida

**Request:**

```json
{
    "email": "mariana@usp.br",
    "password": "SenhaSegura#123",
    "mode": "spa",
    "remember": false,
    "device_name": null
}
```

**Validação:**

- `email` → `required|string|email|max:150`
- `password` → `required|string|min:8|max:128`
- `mode` → `required|in:spa,token`
- `remember` → `boolean`
- `device_name` → `required_if:mode,token|string|max:60`

**Response 200 (`mode: spa`):**
Cookie `laravel_session` (HttpOnly, Secure, SameSite=lax) + body:

```json
{
    "status": "ok",
    "user": { "id": "01J5K3B5GTYV8E2F1W0M8P2XQA", "email": "mariana@usp.br" }
}
```

**Response 200 (`mode: token`):**

```json
{
    "access_token": "1|aBc123...",
    "abilities": ["convites.view", "reservar"],
    "user": { "id": "01J...", "email": "..." }
}
```

**Erros:**

- `401 Unauthenticated` — credenciais inválidas
- `422 ValidationError` — payload inválido (`details.fields`)
- `429 RateLimitExceeded` — mais de 5/min (`Retry-After`, `X-RateLimit-*`)

### 2.2 `POST /api/v1/auth/logout`

- **Route name:** `api.v1.auth.logout`
- **Middlewares:** `auth:sanctum` + `throttle:api`
- **Response:** `204 No Content` (revoga sessão SPA ou deleta `personal_access_tokens` do bearer)

### 2.3 `GET /api/v1/me`

- **Route name:** `api.v1.me`
- **Middlewares:** `auth:sanctum` + `throttle:api`

**Response 200:**

```json
{
    "data": {
        "id": "01J5K3B5GTYV8E2F1W0M8P2XQA",
        "nome": "Mariana Souza",
        "email": "mariana@usp.br",
        "tipo": "formando",
        "roles": ["formando"],
        "abilities": ["convites.view", "reservar", "adesao.criar"],
        "formandos": [
            {
                "id": "01J...",
                "turma": { "id": "01J...", "codigo": "MED-2026" },
                "evento": { "id": "01J...", "slug": "baile-med-usp-2026" }
            }
        ],
        "links": { "self": "https://api.portalartfinal.com.br/api/v1/me" }
    }
}
```

### 2.4 `GET /sanctum/csrf-cookie`

- **Fora de `/api/v1`** — endpoint nativo do Sanctum.
- **Response:** `204` + cookies `laravel_session` + `XSRF-TOKEN`.
- **Quando chamar:** uma vez antes de qualquer mutação `POST/PUT/PATCH/DELETE`.

### 2.5 Headers obrigatórios

| Header             | Direção | Uso                                                 |
| ------------------ | ------- | --------------------------------------------------- |
| `X-Request-Id`     | req/res | Correlação de logs (ULID). Gerado pelo cliente.     |
| `X-XSRF-TOKEN`     | req     | Lido do cookie `XSRF-TOKEN` (Axios faz automático). |
| `Content-Type`     | req     | `application/json`                                  |
| `Accept`           | req     | `application/json`                                  |
| `X-Requested-With` | req     | `XMLHttpRequest` (marca como AJAX para o Laravel).  |

---

## 3. Backend — Laravel 13

### 3.1 Arquivos a criar/modificar

| Arquivo                                          | Ação      | Responsabilidade                                 |
| ------------------------------------------------ | --------- | ------------------------------------------------ |
| `config/sanctum.php`                             | Modificar | `stateful` domains (localhost:5173, portal.\*).  |
| `config/cors.php`                                | Publicar  | `supports_credentials: true`, `allowed_origins`. |
| `bootstrap/app.php`                              | Modificar | Middleware `EnsureFrontendRequestsAreStateful`.  |
| `routes/api/v1.php`                              | Modificar | Registrar 3 rotas (login/logout/me).             |
| `app/Http/Controllers/Api/V1/AuthController.php` | Criar     | `login()`, `logout()`, `me()`.                   |
| `app/Http/Requests/Api/V1/Auth/LoginRequest.php` | Criar     | FormRequest com regras.                          |
| `app/Http/Resources/V1/MeResource.php`           | Criar     | Serialização do formando autenticado.            |
| `app/Http/Middleware/AttachRequestId.php`        | Criar     | Garante `X-Request-Id` sempre.                   |
| `app/Providers/RateLimiterServiceProvider.php`   | Modificar | Registra limiter `login` (5/min por email+ip).   |
| `routes/portal.php`                              | Criar     | Catch-all → `spa.blade.php`.                     |
| `resources/views/spa.blade.php`                  | Criar     | Shell SPA com `@viteReactRefresh`.               |
| `tests/Feature/Api/V1/Auth/LoginTest.php`        | Criar     | 6 cenários Pest.                                 |
| `tests/Feature/Api/V1/Auth/MeTest.php`           | Criar     | 3 cenários Pest.                                 |
| `tests/Feature/Api/V1/Auth/LogoutTest.php`       | Criar     | 2 cenários Pest.                                 |

### 3.2 `config/sanctum.php` — trecho crítico

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS',
    'localhost,localhost:5173,127.0.0.1,'.parse_url(env('APP_URL'), PHP_URL_HOST)
)),
'guard' => ['web'],
'expiration' => null, // sessão: sem expiração no token; cookie expira pelo session lifetime
```

### 3.3 `config/cors.php`

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'webhooks/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_headers' => [
        'Content-Type', 'Accept', 'Authorization',
        'X-Requested-With', 'X-Request-Id', 'X-Idempotency-Key',
        'X-XSRF-TOKEN',
    ],
    'exposed_headers' => ['X-Request-Id', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 3.4 `AuthController` — esqueleto

```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\V1\MeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw new \Illuminate\Auth\AuthenticationException(
                'Credenciais inválidas.'
            );
        }

        $user = Auth::user();

        if ($validated['mode'] === 'spa') {
            $request->session()->regenerate();
            return response()->json([
                'status' => 'ok',
                'user'   => ['id' => $user->ulid, 'email' => $user->email],
            ]);
        }

        // mode: token
        $token = $user->createToken($validated['device_name'])->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'abilities'    => $user->getAllPermissions()->pluck('name'),
            'user'         => ['id' => $user->ulid, 'email' => $user->email],
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new MeResource($request->user()->load(['formandos.turma', 'formandos.evento', 'roles', 'permissions']));
    }
}
```

### 3.5 `LoginRequest`

```php
<?php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email', 'max:150'],
            'password'    => ['required', 'string', 'min:8', 'max:128'],
            'mode'        => ['required', 'in:spa,token'],
            'remember'    => ['sometimes', 'boolean'],
            'device_name' => ['required_if:mode,token', 'nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe seu e-mail.',
            'email.email'    => 'E-mail inválido.',
            'password.min'   => 'Senha deve ter pelo menos 8 caracteres.',
            'mode.in'        => 'Modo de autenticação inválido.',
        ];
    }
}
```

### 3.6 Rate limiter `login`

Em `RateLimiterServiceProvider::boot()`:

```php
RateLimiter::for('login', function (Request $request) {
    $email = (string) $request->input('email', '');
    $key = sha1(strtolower($email).'|'.$request->ip());
    return Limit::perMinute(5)->by($key)->response(function () {
        // envelope § error-envelope.md §2
        return response()->json([
            'error'      => 'RateLimitExceeded',
            'message'    => 'Limite de tentativas excedido. Tente novamente em instantes.',
            'details'    => null,
            'request_id' => request()->header('X-Request-Id'),
            'timestamp'  => now()->toIso8601String(),
        ], 429);
    });
});
```

### 3.7 Testes Pest (mínimo obrigatório)

```php
// LoginTest.php
it('faz login SPA e recebe cookie de sessão', function () {
    $formando = Formando::factory()->create(['email' => 'ana@x.com', 'password' => bcrypt('senha12345')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ana@x.com',
        'password' => 'senha12345',
        'mode' => 'spa',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('user.email', 'ana@x.com')
        ->assertCookie('laravel_session');
});

it('retorna 401 em credenciais inválidas', function () { /* ... */ });
it('retorna 422 quando email ausente com envelope de erro', function () { /* ... */ });
it('retorna 429 após 5 tentativas em 1 min', function () { /* ... */ });
it('exige device_name quando mode=token', function () { /* ... */ });
it('mode=token retorna access_token e não seta cookie', function () { /* ... */ });
```

---

## 4. Frontend — React 19 SPA

### 4.1 Arquivos a criar/modificar

| Arquivo                                               | Ação  | Responsabilidade                                                     |
| ----------------------------------------------------- | ----- | -------------------------------------------------------------------- |
| `resources/spa/src/api/client.ts`                     | Criar | Axios instance + 4 interceptors (CSRF, X-Request-Id, ApiError, 401). |
| `resources/spa/src/api/errors.ts`                     | Criar | Classe `ApiError` tipada.                                            |
| `resources/spa/src/api/hooks/use-auth.ts`             | Criar | `useLogin`, `useLogout`, `useMe`.                                    |
| `resources/spa/src/stores/auth-store.ts`              | Criar | Zustand store (`user`, `isAuthenticated`, `logout`).                 |
| `resources/spa/src/forms/auth/login.schema.ts`        | Criar | Schema Zod com mensagens PT-BR.                                      |
| `resources/spa/src/routes/__root.tsx`                 | Criar | Root layout com QueryClientProvider + TamaguiProvider.               |
| `resources/spa/src/routes/index.tsx`                  | Criar | Redirect `/` → `/login` ou `/portal/home`.                           |
| `resources/spa/src/routes/login.tsx`                  | Criar | Tela `/login` com `<LoginForm>`.                                     |
| `resources/spa/src/routes/portal/_layout.tsx`         | Criar | Guard: sem auth → `/login?redirect=<from>`.                          |
| `resources/spa/src/components/auth/LoginForm.tsx`     | Criar | Form RHF + Zod + submit mutation.                                    |
| `resources/spa/tests/unit/auth-store.test.ts`         | Criar | 5 testes Vitest.                                                     |
| `resources/spa/tests/integration/login-form.test.tsx` | Criar | 4 testes RTL + MSW.                                                  |
| `resources/spa/tests/e2e/login.spec.ts`               | Criar | 3 cenários Playwright (happy, wrong pwd, redirect).                  |

### 4.2 `api/client.ts`

```typescript
import axios, { AxiosError } from 'axios';
import { ApiError } from './errors';
import { useAuthStore } from '@/stores/auth-store';

export const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Interceptor 1 — CSRF antes de mutações
api.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method ?? '')) {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    }
    return config;
});

// Interceptor 2 — X-Request-Id
api.interceptors.request.use((config) => {
    config.headers['X-Request-Id'] = crypto.randomUUID();
    return config;
});

// Interceptor 3 — Error envelope → ApiError
api.interceptors.response.use(
    (r) => r,
    (
        err: AxiosError<{
            error: string;
            message: string;
            details?: { fields?: Record<string, string[]> };
            request_id: string;
        }>,
    ) => {
        const data = err.response?.data;
        throw new ApiError(
            data?.error ?? 'InternalServerError',
            data?.message ?? 'Erro inesperado',
            data?.details ?? null,
            data?.request_id ?? '',
            err.response?.status ?? 500,
        );
    },
);

// Interceptor 4 — 401 handler global
api.interceptors.response.use(undefined, (err) => {
    if (err instanceof ApiError && err.status === 401) {
        useAuthStore.getState().logout();
        const current = window.location.pathname + window.location.search;
        window.location.assign(`/login?redirect=${encodeURIComponent(current)}`);
    }
    return Promise.reject(err);
});
```

### 4.3 `stores/auth-store.ts`

```typescript
import { create } from 'zustand';
import type { FormandoMe } from '@/api/types.gen';

interface AuthState {
    user: FormandoMe | null;
    isAuthenticated: boolean;
    setUser: (u: FormandoMe) => void;
    logout: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
    user: null,
    isAuthenticated: false,
    setUser: (user) => set({ user, isAuthenticated: true }),
    logout: () => set({ user: null, isAuthenticated: false }),
}));
```

### 4.4 `api/hooks/use-auth.ts`

```typescript
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '../client';
import { useAuthStore } from '@/stores/auth-store';
import type { LoginPayload, FormandoMe } from '../types.gen';

export const queryKeys = { me: ['auth', 'me'] as const };

export function useMe() {
    return useQuery({
        queryKey: queryKeys.me,
        queryFn: async () => {
            const { data } = await api.get<{ data: FormandoMe }>('/me');
            return data.data;
        },
        staleTime: 1000 * 60 * 5,
        retry: false, // 401 não deve re-tentar
    });
}

export function useLogin() {
    const qc = useQueryClient();
    const setUser = useAuthStore((s) => s.setUser);
    return useMutation({
        mutationFn: async (payload: LoginPayload) => {
            await api.post('/auth/login', { ...payload, mode: 'spa' });
            const { data } = await api.get<{ data: FormandoMe }>('/me');
            return data.data;
        },
        onSuccess: (user) => {
            setUser(user);
            qc.setQueryData(queryKeys.me, user);
        },
    });
}

export function useLogout() {
    const qc = useQueryClient();
    const logout = useAuthStore((s) => s.logout);
    return useMutation({
        mutationFn: async () => {
            await api.post('/auth/logout');
        },
        onSuccess: () => {
            logout();
            qc.clear();
        },
    });
}
```

### 4.5 `forms/auth/login.schema.ts`

```typescript
import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string({ required_error: 'Informe seu e-mail.' }).email('E-mail inválido.'),
    password: z.string({ required_error: 'Informe sua senha.' }).min(8, 'Mínimo 8 caracteres.'),
    remember: z.boolean().optional().default(false),
});

export type LoginFormData = z.infer<typeof loginSchema>;
```

### 4.6 `routes/portal/_layout.tsx` — Guard

```typescript
import { createFileRoute, redirect, Outlet } from '@tanstack/react-router'
import { useAuthStore } from '@/stores/auth-store'

export const Route = createFileRoute('/portal/_layout')({
  beforeLoad: ({ location }) => {
    if (!useAuthStore.getState().isAuthenticated) {
      throw redirect({
        to: '/login',
        search: { redirect: location.href },
      })
    }
  },
  component: () => <Outlet />,
})
```

### 4.7 Tratamento de erros (por código)

| `ApiError.error`      | HTTP | UX no `LoginForm`                                   |
| --------------------- | ---- | --------------------------------------------------- |
| `Unauthenticated`     | 401  | Inline error: "E-mail ou senha incorretos."         |
| `ValidationError`     | 422  | `setError` do RHF em cada `details.fields[name]`.   |
| `RateLimitExceeded`   | 429  | Toast: "Muitas tentativas. Aguarde {Retry-After}s." |
| `InternalServerError` | 5xx  | Toast: "Erro interno. ID: {request_id}."            |

---

## 5. Ordem de implementação (BE → FE → E2E)

### 5.1 Gate A — Backend foundation (blockers B1-B7)

1. Publicar `config/cors.php` com `supports_credentials: true`.
2. Configurar `config/sanctum.php` stateful domains.
3. Criar `spa.blade.php` com `@viteReactRefresh + @vite(['resources/spa/src/main.tsx'])`.
4. Criar catch-all em `routes/portal.php` → `spa.blade.php`.
5. Registrar middleware `EnsureFrontendRequestsAreStateful` em `bootstrap/app.php` para `api/*`.
6. Criar `AttachRequestId` middleware global.
7. Registrar rate limiter `login` no `RateLimiterServiceProvider`.

> **Gate A done quando:** `curl http://localhost/sanctum/csrf-cookie -v` retorna 204 + `XSRF-TOKEN`.

### 5.2 Gate B — Endpoints auth

8. Criar `LoginRequest` + `AuthController@login` + `AuthController@logout` + `AuthController@me`.
9. Criar `MeResource`.
10. Registrar 3 rotas em `routes/api/v1.php` com prefix `auth.`.
11. Rodar migrations se `formandos` ainda não existe (geralmente vem de SPEC prévia / seed).
12. Escrever os 11 testes Pest (6 login + 3 me + 2 logout).

> **Gate B done quando:** `php artisan test --filter=Auth` com 11/11 verdes.

### 5.3 Gate C — Frontend foundation

13. `cd resources/spa && npm install` (React 19, TanStack, Zustand, RHF, Zod, Axios, Tamagui, openapi-typescript).
14. Criar `vite.config.ts`, `tsconfig.json` (strict + `noUncheckedIndexedAccess`), `main.tsx`.
15. Rodar `npm run codegen` → gera `src/api/types.gen.ts` do `docs/api/openapi-skeleton.yaml`.
16. Criar `api/client.ts`, `api/errors.ts`.
17. Criar `stores/auth-store.ts`.

> **Gate C done quando:** `npm run typecheck` verde + `main.tsx` monta `<div>` de teste.

### 5.4 Gate D — Tela de login

18. Criar `forms/auth/login.schema.ts`.
19. Criar `api/hooks/use-auth.ts` com `useLogin/useLogout/useMe`.
20. Criar `routes/__root.tsx`, `routes/index.tsx` (redirect), `routes/login.tsx`, `routes/portal/_layout.tsx` (guard), `routes/portal/home.tsx` (placeholder).
21. Criar `components/auth/LoginForm.tsx` com Tamagui + RHF.
22. Smoke test manual: `/login` → credenciais válidas → `/portal/home`.

> **Gate D done quando:** smoke manual passa em 3 browsers (Chromium, Firefox, WebKit).

### 5.5 Gate E — Testes

23. Escrever `auth-store.test.ts` (5 testes unit Vitest).
24. Escrever `login-form.test.tsx` (4 testes integration + MSW).
25. Escrever `login.spec.ts` (3 cenários Playwright).
26. CI: `npm run quality` (lint + typecheck + test) + `php artisan test`.

> **Gate E done quando:** todos os testes verdes no CI + coverage ≥ 70%.

---

## 6. Critérios de aceite (Gherkin PT-BR)

### CA-001 — Login happy path

```gherkin
Dado que sou um formando cadastrado com email "ana@usp.br" e senha "Senha1234"
Quando acesso "/login"
E preencho email com "ana@usp.br"
E preencho senha com "Senha1234"
E clico em "Entrar"
Então a requisição POST /api/v1/auth/login retorna 200
E o cookie "laravel_session" é setado como HttpOnly
E sou redirecionado para "/portal/home"
E o nome "Ana" aparece no header do portal
```

### CA-002 — Credenciais inválidas

```gherkin
Dado que acesso "/login"
Quando submeto email "ana@usp.br" e senha "errada"
Então vejo a mensagem inline "E-mail ou senha incorretos."
E permaneço em "/login"
E o cookie de sessão não é setado
```

### CA-003 — Validação de campos

```gherkin
Dado que acesso "/login"
Quando clico em "Entrar" sem preencher email
Então vejo "Informe seu e-mail." abaixo do campo email
E não há chamada a POST /auth/login
```

### CA-004 — Rate limit

```gherkin
Dado que já tentei login 5 vezes em 1 minuto com mesmo email
Quando submeto a 6ª tentativa
Então recebo toast "Muitas tentativas. Aguarde Xs."
E o campo Retry-After é respeitado pelo botão (desabilitado durante o período)
```

### CA-005 — Guard de rota protegida

```gherkin
Dado que não estou autenticado
Quando acesso diretamente "/portal/mesas"
Então sou redirecionado para "/login?redirect=%2Fportal%2Fmesas"
E após login bem-sucedido volto para "/portal/mesas"
```

### CA-006 — Logout

```gherkin
Dado que estou autenticado em "/portal/home"
Quando clico em "Sair"
Então POST /api/v1/auth/logout retorna 204
E o cookie laravel_session é invalidado
E sou redirecionado para "/login"
E tentar voltar para "/portal/home" redireciona de novo para "/login"
```

### CA-007 — 401 em rota protegida (sessão expirada)

```gherkin
Dado que estou autenticado em "/portal/financeiro"
E o cookie de sessão expira no backend
Quando qualquer request retorna 401
Então o interceptor limpa o auth-store
E sou redirecionado para "/login?redirect=%2Fportal%2Ffinanceiro"
```

---

## 7. Estratégia de testes

| Camada         | Arquivo                                       | Casos                                                                 |
| -------------- | --------------------------------------------- | --------------------------------------------------------------------- |
| Unit FE        | `tests/unit/auth-store.test.ts`               | setUser, logout, estado inicial, isAuthenticated, reset.              |
| Unit FE        | `tests/unit/login.schema.test.ts`             | Zod: email obrigatório, email inválido, senha < 8, remember opcional. |
| Integration FE | `tests/integration/login-form.test.tsx` + MSW | Happy, 401 inline, 422 field errors, 429 toast.                       |
| Unit BE (Pest) | `tests/Unit/LoginRequestTest.php`             | Regras de validação por campo.                                        |
| Feature BE     | `tests/Feature/Api/V1/Auth/LoginTest.php`     | 6 cenários (ver §3.7).                                                |
| Feature BE     | `tests/Feature/Api/V1/Auth/MeTest.php`        | 200 autenticado; 401 sem auth; shape do JSON.                         |
| Feature BE     | `tests/Feature/Api/V1/Auth/LogoutTest.php`    | 204 SPA; 204 bearer revogando token.                                  |
| E2E            | `tests/e2e/login.spec.ts`                     | CA-001, CA-002, CA-005.                                               |
| Smoke          | `npm run smoke`                               | `/login` carrega sem erro console; `/` redireciona.                   |

**Coverage alvo:** auth-store 90% · LoginForm 80% · AuthController 100% · global ≥ 70%.

---

## 8. Blockers + open questions

### 8.1 Blockers BE (referenciam [14-OPEN-QUESTIONS](../frontend/14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md))

- ❌ **B1** — `config/cors.php` precisa ser publicado.
- ❌ **B2** — `config/sanctum.php` com stateful domains corretos.
- ❌ **B3** — `routes/portal.php` com catch-all.
- ❌ **B4** — `resources/views/spa.blade.php`.
- ❌ **B5-B7** — Os 3 endpoints desta spec.
- ❌ **B9** — Error envelope global (pré-requisito para o interceptor 3 funcionar).
- ❌ **B16** — CORS `allowed_headers` deve incluir `X-Request-Id`.

### 8.2 Open questions

- **❓ D7** — Recuperação de senha entra nesta SPEC ou é SPEC separada (SPEC-001.1)? _Default proposto:_ SPEC separada em F3 final.
- **❓ OQ-1** — Remember me estende cookie lifetime? _Proposto:_ `remember: true` → `config/session.php lifetime = 60*24*30` (30 dias).
- **❓ OQ-2** — Logout em uma aba limpa todas as outras abas? _Proposto:_ usar `BroadcastChannel('auth')` para sincronizar.
- **❓ OQ-3** — Qual deve ser o TTL do `X-Request-Id`? _Proposto:_ gerar novo por request, sem cache.

---

## 9. Matriz de rastreabilidade

| RF ([04-SRS](../frontend/04-FRONTEND-SRS.md)) | Endpoint            | Hook/Componente FE               | Teste (BE)                 | Teste (FE)                 |
| --------------------------------------------- | ------------------- | -------------------------------- | -------------------------- | -------------------------- |
| RF-001 Autenticar via Sanctum cookie          | `POST /auth/login`  | `useLogin` · `LoginForm`         | `LoginTest::faz login SPA` | `login-form.test::happy`   |
| RF-002 Armazenar estado auth                  | —                   | `useAuthStore`                   | —                          | `auth-store.test::setUser` |
| RF-003 Proteger rotas via guard               | —                   | `portal/_layout.tsx::beforeLoad` | —                          | `login.spec::redirect`     |
| RF-XXX Logout                                 | `POST /auth/logout` | `useLogout`                      | `LogoutTest::204`          | `login.spec::logout`       |
| RNF-005 WCAG 2.1 AA                           | —                   | `LoginForm` (ARIA labels, focus) | —                          | `login-form.test::a11y`    |

---

## 10. Cross-refs

**Backend:**

- [PLANEJAMENTO_BACKEND_APIV1.md §2.4 (Sanctum dual-mode)](../prd/PLANEJAMENTO_BACKEND_APIV1.md)
- [api-contract.md §1 (endpoints auth)](../api/api-contract.md)
- [error-envelope.md §2-§4](../api/error-envelope.md)
- [ADR backend 0003 — Sanctum dual-mode](../architecture/adrs/ADR-0003-sanctum-dual-mode.md)

**Frontend:**

- [PLANEJAMENTO_FRONTEND_REACT.md §3 (Axios client), §6 (Stores), §11 (Prerequisites)](../prd/PLANEJAMENTO_FRONTEND_REACT.md)
- [05-FRONTEND-SAD.md §6 (runtime auth)](../frontend/05-FRONTEND-SAD.md)
- [08-API-INTEGRATION-CONTRACT.md (auth flow completo)](../frontend/08-API-INTEGRATION-CONTRACT.md)
- [09-TECHNICAL-DESIGN-CRITICAL-MODULES.md §1 (módulo auth)](../frontend/09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- [ADR-008 — Sanctum stateful cookie web](../frontend/06-ADR/ADR-008-sanctum-stateful-cookie-web.md)
- [04-FRONTEND-SRS.md §1 (RF-001 a RF-003)](../frontend/04-FRONTEND-SRS.md)

**Próximas SPECs que dependem desta:**

- [SPEC-002 — Wizard de Adesão](./SPEC-002-wizard-adesao.md) _(a criar)_
- [SPEC-003 — Financeiro e Pagamento](./SPEC-003-financeiro-pagamento.md) _(a criar)_
- [SPEC-006 — Mapa de Mesas](./SPEC-006-mapa-mesas-seating.md) _(a criar)_
