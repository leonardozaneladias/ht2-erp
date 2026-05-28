---
title: Planejamento Frontend React — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: accepted
---

# Planejamento Frontend React — Portal ArtFinal v2

> Documento mestre do SPA React para o Portal do Formando. Equivalente ao `PLANEJAMENTO_BACKEND_APIV1.md` mas focado na camada de apresentação web e mobile. Leia antes de escrever qualquer linha de código React.

---

## §0 — Princípios Não Negociáveis

1. **SPA React puro no portal.** Nenhuma view Blade de portal (exceto o shell `spa.blade.php`).
2. **API-first via `/api/v1`.** O SPA consome exclusivamente o contrato documentado em `docs/api/`.
3. **TypeScript estrito desde F3.** `strict: true`, `noUncheckedIndexedAccess`, zero `any`.
4. **Sanctum stateful (cookie) para web.** `withCredentials: true`, CSRF via `GET /sanctum/csrf-cookie`.
5. **100% PT-BR na UI.** Textos, labels, mensagens de erro, toasts.
6. **ULID em todas as rotas.** Nunca expor BIGINT interno em URLs ou params.
7. **Idempotência obrigatória** em seating e pagamentos (`X-Idempotency-Key` em `sessionStorage`).
8. **Hold timer real.** Temporizador sincronizado com `hold_expires_at` do servidor, nunca apenas local.
9. **Cursor pagination.** Nunca offset. Sempre `cursor` + `next_cursor` nos hooks TanStack Query.
10. **openapi-typescript codegen em CI.** Tipos gerados de `docs/api/openapi-skeleton.yaml`; nunca tipos manuais para contrato de API.

---

## §1 — Stack Tecnológica (imutável)

| Camada        | Pacote                         | Versão               |
| ------------- | ------------------------------ | -------------------- |
| Bundler       | Vite                           | 7.x (já instalado)   |
| UI Framework  | React                          | 19.x                 |
| Linguagem     | TypeScript                     | 5.x                  |
| Roteamento    | TanStack Router                | v1 (file-based)      |
| Data fetching | TanStack Query                 | v5                   |
| Estado global | Zustand                        | v5                   |
| Formulários   | React Hook Form                | v7                   |
| Validação     | Zod                            | v4                   |
| HTTP client   | Axios                          | v1                   |
| Design system | shadcn/ui + Radix UI           | latest (Tailwind v4) |
| Testes        | Vitest + RTL + Playwright      | —                    |
| Tipos de API  | openapi-typescript             | v7                   |
| i18n          | hardcoded PT-BR (fase inicial) | —                    |

> **Mobile (F8):** React Native + Expo SDK 53. **Decisão revisada (2026-04-25):** o portal pivotou de Tamagui para shadcn/ui + Radix UI; o app mobile (se acontecer) terá codebase próprio. Trade-off discutido no §9.

---

## §2 — Estrutura de Diretórios

```
resources/
├── spa/                        ← raiz do SPA React
│   ├── src/
│   │   ├── main.tsx            ← entry point (monta RouterProvider)
│   │   ├── app/
│   │   │   ├── router.tsx      ← TanStack Router config
│   │   │   ├── query-client.ts ← QueryClient config global
│   │   │   └── store.ts        ← Zustand store raiz
│   │   ├── api/
│   │   │   ├── client.ts       ← Axios instance + interceptors
│   │   │   ├── types.gen.ts    ← gerado por openapi-typescript (não editar)
│   │   │   └── hooks/          ← um arquivo por recurso
│   │   │       ├── use-auth.ts
│   │   │       ├── use-adesao.ts
│   │   │       ├── use-pagamento.ts
│   │   │       ├── use-seating.ts
│   │   │       ├── use-convites.ts
│   │   │       ├── use-extras.ts
│   │   │       └── use-enquetes.ts
│   │   ├── stores/
│   │   │   ├── auth-store.ts
│   │   │   ├── wizard-store.ts ← estado do wizard adesão (7 etapas)
│   │   │   └── hold-store.ts   ← hold timer seating
│   │   ├── components/
│   │   │   ├── ui/             ← componentes shadcn customizados (Button, Input, Card, Select, etc.)
│   │   │   ├── layout/         ← AppShell, Header, BottomNav
│   │   │   ├── wizard/         ← etapas do wizard
│   │   │   ├── seating/        ← mapa de mesas interativo
│   │   │   └── shared/         ← ErrorBoundary, LoadingSpinner, etc.
│   │   ├── forms/
│   │   │   ├── adesao/         ← schemas Zod + RHF por etapa
│   │   │   └── pagamento/
│   │   ├── routes/             ← TanStack Router file-based routes
│   │   │   ├── __root.tsx
│   │   │   ├── index.tsx       ← redirect → /portal/login
│   │   │   ├── login.tsx
│   │   │   ├── portal/
│   │   │   │   ├── _layout.tsx ← guard auth
│   │   │   │   ├── home.tsx
│   │   │   │   ├── adesao/
│   │   │   │   │   └── $step.tsx
│   │   │   │   ├── financeiro.tsx
│   │   │   │   ├── pagamento/$parcela_ulid.tsx
│   │   │   │   ├── convites.tsx
│   │   │   │   ├── mesas.tsx
│   │   │   │   ├── extras.tsx
│   │   │   │   ├── enquetes.tsx
│   │   │   │   └── perfil.tsx
│   │   │   └── rsvp/$token.tsx ← público (sem auth)
│   │   └── lib/
│   │       ├── idempotency.ts  ← X-Idempotency-Key helpers
│   │       ├── money.ts        ← centavos → R$ formatação
│   │       ├── ulid.ts         ← validação e parse
│   │       └── date.ts         ← formatação PT-BR
│   ├── tsconfig.json
│   ├── vite.config.ts          ← separado do admin
│   └── index.html              ← não usar; shell é spa.blade.php
├── views/
│   └── spa.blade.php           ← shell único servido pelo Laravel
└── css/
    └── spa/styles/             ← Tailwind v4 + tokens.css (brand Art Final) + globals.css
```

---

## §3 — Axios Client

**Arquivo:** `resources/spa/src/api/client.ts`

```typescript
import axios from 'axios';

export const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true, // Sanctum cookie
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

// 1. CSRF: buscar cookie antes de mutações
api.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method ?? '')) {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    }
    return config;
});

// 2. X-Request-Id para correlação de logs
api.interceptors.request.use((config) => {
    config.headers['X-Request-Id'] = crypto.randomUUID();
    return config;
});

// 3. Error envelope parser → lança ApiError tipado
api.interceptors.response.use(
    (r) => r,
    (err) => {
        const data = err.response?.data;
        throw new ApiError(data?.message ?? 'Erro inesperado', data?.errors, err.response?.status);
    },
);

// 4. 401 → limpar auth store + redirect login
api.interceptors.response.use(undefined, (err) => {
    if (err.response?.status === 401) useAuthStore.getState().logout();
    return Promise.reject(err);
});
```

---

## §4 — TanStack Query

**Config:** `resources/spa/src/app/query-client.ts`

- `staleTime: 1000 * 60` (1 min padrão)
- `retry: 1` (apenas uma re-tentativa para falhas de rede)
- `refetchOnWindowFocus: false` em dev
- Cursor pagination via `useInfiniteQuery` com `getNextPageParam: (last) => last.meta.next_cursor`

**Convenção de query keys:**

```typescript
export const queryKeys = {
    auth: ['auth', 'me'] as const,
    adesao: (turmaUlid: string) => ['adesao', turmaUlid] as const,
    parcelas: (adesaoUlid: string) => ['parcelas', adesaoUlid] as const,
    mesas: (eventoUlid: string) => ['mesas', eventoUlid] as const,
    convites: (adesaoUlid: string) => ['convites', adesaoUlid] as const,
};
```

---

## §5 — TanStack Router (11 rotas)

| Rota                              | Arquivo                       | Auth         |
| --------------------------------- | ----------------------------- | ------------ |
| `/`                               | `index.tsx`                   | — (redirect) |
| `/login`                          | `login.tsx`                   | público      |
| `/portal`                         | `_layout.tsx`                 | guard        |
| `/portal/home`                    | `home.tsx`                    | sim          |
| `/portal/adesao/$step`            | `adesao/$step.tsx`            | sim          |
| `/portal/financeiro`              | `financeiro.tsx`              | sim          |
| `/portal/pagamento/$parcela_ulid` | `pagamento/$parcela_ulid.tsx` | sim          |
| `/portal/convites`                | `convites.tsx`                | sim          |
| `/portal/mesas`                   | `mesas.tsx`                   | sim          |
| `/portal/extras`                  | `extras.tsx`                  | sim          |
| `/portal/enquetes`                | `enquetes.tsx`                | sim          |
| `/portal/perfil`                  | `perfil.tsx`                  | sim          |
| `/rsvp/$token`                    | `rsvp/$token.tsx`             | público      |

**Guard (`_layout.tsx`):** verifica `useAuthStore` → se não autenticado, redireciona para `/login` com `redirect` param.

---

## §6 — Zustand Stores

### `auth-store.ts`

```typescript
interface AuthState {
    user: FormandoMe | null;
    isAuthenticated: boolean;
    login: (credentials: LoginPayload) => Promise<void>;
    logout: () => void;
}
```

### `wizard-store.ts` (7 etapas wizard adesão)

```typescript
interface WizardState {
    currentStep: 1 | 2 | 3 | 4 | 5 | 6 | 7;
    formData: Partial<WizardFormData>; // acumulado por etapa
    adesaoUlid: string | null; // preenchido após POST /adesoes
    setStep: (step: number) => void;
    setStepData: (step: number, data: Partial<WizardFormData>) => void;
    reset: () => void;
}
// persistido em sessionStorage (não localStorage — dados sensíveis)
```

### `hold-store.ts` (seating hold timer)

```typescript
interface HoldState {
    holdExpiresAt: string | null; // ISO 8601 do servidor
    secondsRemaining: number;
    startTimer: (expiresAt: string) => void;
    clearTimer: () => void;
}
// tick via setInterval; reconcilia com servidor a cada navegação no mapa
```

---

## §7 — Idempotência

**Arquivo:** `resources/spa/src/lib/idempotency.ts`

```typescript
export function getIdempotencyKey(operation: string): string {
    const storageKey = `idempotency:${operation}`;
    const existing = sessionStorage.getItem(storageKey);
    if (existing) return existing;
    const key = crypto.randomUUID();
    sessionStorage.setItem(storageKey, key);
    return key;
}

export function clearIdempotencyKey(operation: string): void {
    sessionStorage.removeItem(`idempotency:${operation}`);
}
```

**Uso:** `POST /adesoes/:ulid/seating` e `POST /pagamentos` incluem `X-Idempotency-Key` gerado por esta função. Limpar após resposta 201.

---

## §8 — Formulários (React Hook Form + Zod)

Cada etapa do wizard tem seu próprio schema Zod em `forms/adesao/step-N.schema.ts`.

```typescript
// Exemplo etapa "dados pessoais" (após escolha de curso+período e pacote)
// Observação: contrato_ulid e turma_ulid são persistidos no adesao-publica-store
// a partir do GET /adesao/publico/{codigo-contrato} e das etapas de seleção.
export const dadosPessoaisSchema = z.object({
    cpf: z.string().regex(/^\d{3}\.\d{3}\.\d{3}-\d{2}$/, 'CPF inválido'),
    telefone: z.string().min(10),
    contrato_ulid: z.string().ulid(),
    turma_ulid: z.string().ulid(),
    pacote_ulid: z.string().ulid(),
});

export type DadosPessoaisData = z.infer<typeof dadosPessoaisSchema>;
```

O RHF usa `zodResolver(stepNSchema)` em cada etapa. O `wizard-store` acumula os dados entre etapas.

---

## §9 — Design System (shadcn/ui + Radix UI) — atualizado 2026-04-25

**Decisão pivotada de Tamagui v2 → shadcn/ui + Radix UI.** Justificativa registrada como ADR informal aqui:

- **Por que pivotamos**: o ecossistema de mercado em 2025/26 convergiu fortemente para shadcn/ui (Vercel, Linear, Stripe). Permite ownership total dos componentes (copy-paste), pareia com Tailwind v4 já no projeto, melhor DX, maior pool de exemplos. Tamagui tem curva mais íngreme e ecossistema menor.
- **Trade-off cross-platform**: o reuso direto no React Native (F8) deixa de existir. Se o app mobile acontecer, terá codebase próprio (pode reusar tipos/schemas/api/zustand). Aceitável: F8 está nas sprints 25–26 (longe), e a maior parte da lógica do portal está em camadas portáveis (TanStack Query, Zustand, Zod).
- **Identidade visual**: tokens em `resources/spa/src/styles/tokens.css` capturam a paleta Art Final Eventos (`#FF3D03` coral + `#ED1566` magenta + neutros warm). Tipografia Fraunces (display) + Plus Jakarta Sans (UI) + JetBrains Mono.
- **Componentes base** em `resources/spa/src/components/ui/`: `button`, `input`, `label`, `select`, `card`, `checkbox`, `progress`, `badge`, `alert`, `separator`, `skeleton`. Cada um customizado para usar `var(--brand-primary)` etc. em vez do default zinc do shadcn.
- **Componentes de wizard** em `components/wizard/`: `WizardShell`, `WizardProgress`, `WizardStepHeader`, `MoneyDisplay`, `SelectableCard`.
- **Componentes brand** em `components/brand/`: `Logo` (variantes full/mark/mono) e `GradientMesh`.

---

## §10 — Mapa de Mesas (F5 — crítico)

Decisão de realtime: **polling curto (5s) via TanStack Query** enquanto hold está ativo. Não implementar WebSocket/Reverb no MVP.

```typescript
useQuery({
    queryKey: queryKeys.mesas(eventoUlid),
    queryFn: () => api.get(`/eventos/${eventoUlid}/mesas`).then((r) => r.data),
    refetchInterval: holdStore.holdExpiresAt ? 5000 : false,
    staleTime: 0,
});
```

**Hold timer:** após `POST /mesas/:id/hold` (resposta 200 com `hold_expires_at`):

1. `holdStore.startTimer(hold_expires_at)`
2. Contagem regressiva visual no componente SeatingMap
3. Expirado → liberar automaticamente + toast de aviso
4. Na confirmação → `POST /mesas/:id/confirm` + `clearIdempotencyKey('seating')`

---

## §11 — Backend Prerequisites (bloqueadores)

Antes de qualquer código React, o backend precisa entregar:

| #   | Item backend                                                                                            | Arquivo Laravel                 |
| --- | ------------------------------------------------------------------------------------------------------- | ------------------------------- |
| 1   | `config/cors.php` publicado com `supports_credentials: true` e `allowed_origins: [env('FRONTEND_URL')]` | `config/cors.php`               |
| 2   | `sanctum.php` com `stateful: ['localhost', 'localhost:5173', env('APP_URL')]`                           | `config/sanctum.php`            |
| 3   | `routes/portal.php` com catch-all servindo `spa.blade.php`                                              | `routes/portal.php`             |
| 4   | `resources/views/spa.blade.php` com `@viteReactRefresh` + `@vite(['resources/spa/src/main.tsx'])`       | `resources/views/spa.blade.php` |
| 5   | `POST /api/v1/auth/login` retornando `{ data: { formando: {...} } }`                                    | `Api/V1/AuthController`         |
| 6   | `GET /api/v1/auth/me` retornando o formando autenticado                                                 | `Api/V1/AuthController`         |
| 7   | `POST /api/v1/auth/logout`                                                                              | `Api/V1/AuthController`         |

---

## §12 — vite.config.ts do SPA

```typescript
// resources/spa/vite.config.ts
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { TanStackRouterVite } from '@tanstack/router-vite-plugin';

export default defineConfig({
    plugins: [react(), TanStackRouterVite()],
    resolve: { alias: { '@': '/resources/spa/src' } },
    build: {
        outDir: '../../public/spa',
        manifest: true,
    },
    server: {
        proxy: {
            '/api': 'http://localhost:80',
            '/sanctum': 'http://localhost:80',
        },
    },
});
```

O `vite.config.js` raiz (do admin) permanece intocado.

---

## §13 — openapi-typescript codegen

```bash
# Instalar
npm install -D openapi-typescript

# Gerar tipos (rodar a cada mudança no contrato)
npx openapi-typescript docs/api/openapi-skeleton.yaml -o resources/spa/src/api/types.gen.ts
```

**Em CI (GitHub Actions):** step antes do build React que regenera `types.gen.ts` e falha o build se houver diff — garante que contrato e tipos ficam sincronizados.

---

## §14 — Cronograma

| Fase          | Sprint alvo | Story Points | Entregas                                                                                                                                                         |
| ------------- | ----------- | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Pré-F3**    | F2 fim      | —            | Setup: TypeScript, Vite config, shadcn/ui + Radix, TanStack Router/Query, Axios client, openapi-typescript codegen, `spa.blade.php`, CORS/Sanctum backend config |
| **F3**        | SP 4–9      | 34 SP        | Login, wizard adesão 7 etapas, dashboard home, financeiro extrato, pagamento (boleto/pix/cartão), Sanctum auth flow completo                                     |
| **F4 UI**     | SP 10–11    | 12 SP        | Carteira de convites, RSVP público, perfil, extras catálogo                                                                                                      |
| **F5 UI**     | SP ~15–17   | 14 SP        | Mapa de mesas interativo, hold timer, confirmação de assento, seating com idempotência                                                                           |
| **F6 UI**     | SP ~18–19   | 12 SP        | Enquetes, notificações in-app, refinamento UX                                                                                                                    |
| **F7**        | SP 24       | 5 SP         | Polish, acessibilidade, Lighthouse ≥ 90, testes E2E Playwright                                                                                                   |
| **F8 Mobile** | SP 25–26    | 34 SP        | React Native + Expo SDK 53, codebase próprio (sem reuso de UI), token auth (ADR-0003); reusar api/stores/schemas                                                 |

---

## Apêndice A — Checklist Pré-F3

Execute todos os itens antes de criar qualquer tela:

- [ ] Instalar React 19, TypeScript 5, `@types/react`
- [ ] Instalar TanStack Router v1 + TanStack Query v5
- [ ] Instalar Zustand v5
- [ ] Instalar React Hook Form v7 + Zod v4
- [ ] Instalar Axios v1
- [x] Instalar shadcn/ui + Radix primitives + tokens brand (concluído 2026-04-25)
- [ ] Instalar `openapi-typescript` como devDependency
- [ ] Criar `resources/spa/vite.config.ts` (separado do admin)
- [ ] Criar `resources/spa/tsconfig.json` com `strict: true`
- [ ] Criar `resources/spa/src/main.tsx` com RouterProvider + QueryClientProvider + TamaguiProvider
- [ ] Criar `resources/views/spa.blade.php` com shell mínimo
- [ ] Adicionar catch-all em `routes/portal.php` → `spa.blade.php`
- [ ] Publicar `config/cors.php` e configurar `supports_credentials: true`
- [ ] Configurar `config/sanctum.php` stateful domains
- [ ] Criar `Api\V1\AuthController` com login/logout/me
- [ ] Gerar `types.gen.ts` do openapi-skeleton.yaml
- [ ] Criar `api/client.ts` com todos os interceptors
- [ ] Criar `stores/auth-store.ts`
- [ ] Configurar Vitest + RTL
- [ ] Smoke test: `GET /sanctum/csrf-cookie` → `POST /api/v1/auth/login` → `GET /api/v1/auth/me`

---

## Apêndice B — Decisões Pendentes (defaults propostos)

| Questão                     | Default proposto                    | Alternativa / Risco                                                |
| --------------------------- | ----------------------------------- | ------------------------------------------------------------------ |
| Localização do código React | `resources/spa/` (monorepo Laravel) | Repo separado: mais flexível, mais overhead de CI                  |
| API typing                  | openapi-typescript v7               | orval: gera hooks automaticamente, mais pesado                     |
| Realtime mapa mesas         | Polling 5s durante hold ativo       | WebSocket/Reverb em F7 se polling causar sobrecarga                |
| i18n                        | Strings hardcoded PT-BR             | i18next em F8 para internacionalização mobile                      |
| Design system               | Tamagui v2 full                     | shadcn/ui apenas se Tamagui for descartado do mobile               |
| Testes E2E                  | Playwright                          | Cypress: mais popular, mais lento                                  |
| TypeScript strictness       | `strict: true` desde F3             | `noUncheckedIndexedAccess` pode ser relaxado nas primeiras sprints |

---

## Apêndice C — Anti-Patterns (proibido)

```
❌ Lógica de negócio em componentes React (usar hooks customizados + stores)
❌ Fetch direto com fetch() (usar Axios client configurado com interceptors)
❌ useState para dados do servidor (usar TanStack Query)
❌ localStorage para dados de sessão (usar sessionStorage; nunca persistir tokens JWT)
❌ any no TypeScript (usar tipos gerados ou unknown + type guard)
❌ Tipos manuais para contrato de API (usar types.gen.ts gerado do openapi-skeleton.yaml)
❌ Offset pagination (cursor apenas — next_cursor do envelope)
❌ Timer de hold apenas local (sempre reconciliar com hold_expires_at do servidor)
❌ BIGINT em URL (ULID apenas em todas as rotas)
❌ Valores monetários como float (centavos int, formatar com lib/money.ts)
❌ Componentes de página como arquivo único gigante (extrair sub-componentes)
❌ Chamadas de API diretas em componentes sem TanStack Query
❌ Múltiplas instâncias de QueryClient (singleton em app/query-client.ts)
❌ Importar de Tamagui e shadcn/ui no mesmo componente
❌ Criar componentes de portal em Blade/Livewire (apenas spa.blade.php é permitido)
```
