---
title: 'ADR-004: TanStack Router v1 + TanStack Query v5 + Zustand v5 como trio core'
adr: 004
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - router
    - state-management
    - data-fetching
    - tanstack
    - zustand
---

# ADR-004: TanStack Router v1 + TanStack Query v5 + Zustand v5 como trio core

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, router, state-management, data-fetching, tanstack, zustand

## 1. Contexto

Uma SPA React precisa decidir três camadas críticas:

1. **Roteamento** — como URLs mapeiam para componentes, com params e search tipados.
2. **Server state** — como buscar, cachear, invalidar e retornar dados do backend.
3. **Client state** — como armazenar estado de UI efêmero (sessão, wizard, hold timer) com persistência opcional.

Cada camada tem libs dominantes em 2025-2026, com trade-offs distintos. A decisão precisa ser coesa: os três componentes se integram bem entre si? A tipagem flui de ponta a ponta?

O Portal ArtFinal tem necessidades específicas:

- **11 rotas** com ULID nos params, search params estruturados (ex.: `/portal/adesao/$step`, `/rsvp/$token`).
- **Polling em seating** (5s via refetchInterval).
- **Cursor pagination** com `next_cursor` em meta.
- **Persistência de wizard** em sessionStorage (7 etapas).
- **Hold timer** com tick por segundo + reconciliação com servidor.
- **Reautenticação** global no 401.

A escolha tem impacto em:

- Tipos fim-a-fim (TS strict).
- Tamanho de bundle.
- Curva de aprendizado.
- DevTools.
- Testabilidade.

## 2. Decisão

**O Portal ArtFinal usa TanStack Router v1 (file-based) + TanStack Query v5 + Zustand v5** como trio core de infraestrutura client.

Responsabilidades:

- **TanStack Router v1**: roteamento file-based, guards de auth (`_layout.tsx`), params e search tipados via plugin Vite que regenera `routeTree.gen.ts` automaticamente. Loaders de rota opcionais integrados com TanStack Query.
- **TanStack Query v5**: tudo que vem do servidor. Hooks por recurso (`use-auth.ts`, `use-adesao.ts`, etc.). `queryKeys` convencionados (§SAD §4). Cursor pagination via `useInfiniteQuery`. Polling via `refetchInterval`.
- **Zustand v5**: estado **client-only** — sessão (`authStore`), wizard (`wizardStore` com `persist sessionStorage`), hold timer (`holdStore`). Nunca armazena dados que vêm do backend (esse é papel de Query).

Regras adicionais:

- **Nunca** usar `useState` para dados que vêm do servidor. Sempre TanStack Query.
- **Nunca** duplicar dados entre Query cache e Zustand store.
- Stores Zustand têm tipo explícito; persistência via middleware `persist` apenas quando justificado (wizard + idempotency).
- Router é o **único** provedor de navegação. `window.location` só em lugares onde o Router ainda não montou (ex.: interceptor Axios no 401 → fallback para `window.location.href`).

## 3. Consequências

### Positivas

- **Tipagem fim-a-fim**: TanStack Router gera tipos dos params, search, loaders. Hooks TanStack Query têm tipo derivado do retorno. Zustand tem tipo de state declarado. TS strict funciona sem `any`.
- **File-based routing**: adicionar uma rota é criar um arquivo em `routes/`. Zero boilerplate de registro manual.
- **Co-localização de loader + componente**: TanStack Router permite `loader` na mesma rota, que pré-popula `queryClient` via TanStack Query — reduz cascata de loading.
- **DevTools ricas**: TanStack Router DevTools + TanStack Query DevTools mostram rotas, cache, invalidações, mutations em tempo real.
- **Bundle competitivo**: Router ~25 KB gzip, Query ~13 KB gzip, Zustand ~1 KB gzip → total ~39 KB gzip, comparável a React Router (18 KB) mas com muito mais features.
- **Integração orgânica**: TanStack Router e Query são co-maintained; exemplos oficiais mostram integração profunda (invalidate on navigate, loader → query prefetch).
- **Zustand**: simplicidade extrema. Store é função, não classe; testável como função pura.
- **Persist middleware** do Zustand cobre sessionStorage nativamente — sem custom hooks.

### Negativas

- **File-based routing** é curva de aprendizado para quem vem do React Router v6. Mitigação: 5 exemplos no README + sessão onboarding em F3.
- **TanStack Router** é mais jovem que React Router (GA v1 em 2024). Ecossistema de plugins menor.
- **Memoization em Zustand**: selectors mal escritos rerenderizam demais. Mitigação: convenção de usar `useShallow` + selectors atômicos.
- **Sincronização entre Query e Zustand em casos raros** (ex.: auth): `authStore` guarda user; TanStack Query expõe `useMe()`. Precisamos decidir: Zustand é fonte (authStore sincroniza via onSuccess de login) ou Query é fonte. Decisão: **Zustand é fonte para sessão**, porque `onMount` do app consulta `authStore` antes de qualquer query. `useMe()` apenas refresha o `authStore` quando necessário.

## 4. Trade-offs

| Ganhamos                                                                   | Perdemos                                                       |
| -------------------------------------------------------------------------- | -------------------------------------------------------------- |
| Tipagem fim-a-fim automática (Router + Query + Zustand)                    | Curva de aprendizado file-based routing                        |
| Bundle ~39 KB vs RTK (~50 KB)                                              | Ecossistema ainda menor que React Router                       |
| DevTools ricas integradas                                                  | TanStack Router mais jovem (mais risco de bug ocasional)       |
| Boilerplate mínimo (Router gera, Query cacheia, Zustand store em 5 linhas) | Duas libs novas para devs acostumados com React Router + Redux |
| Integração Router ↔ Query nativa (loaders, invalidate on nav)             | Sincronização Query ↔ Zustand requer disciplina em auth       |
| Polling, cursor pagination e retries grátis (Query)                        | Sem "batteries included" para formulários (precisamos de RHF)  |

## 5. Alternativas rejeitadas

### Alt 1: React Router v6 + SWR

- **Prós**: React Router é padrão; SWR é Vercel-backed e simples.
- **Contras**:
    - **Tipagem mais fraca**: params de React Router são `string | undefined` — exige `assertParamsAreValid()` manual.
    - **File-based routing** não é nativo (precisa de plugin como `@generouted`).
    - **SWR tem API menor que TanStack Query** (sem cursor pagination nativo, sem retries configuráveis, sem mutations).
    - **Sem loader integrado** com data fetcher: cascata de loading mais pronunciada.

### Alt 2: React Router v6.4+ (com data routers e loaders) + TanStack Query

- **Prós**: loaders do React Router v6.4+ mitigam cascata; bundle menor que Router; comunidade gigante.
- **Contras**:
    - **Loaders do React Router** duplicam a função de TanStack Query se usados juntos. Viram concorrentes em vez de sinérgicos.
    - **Tipagem de params**: ainda menos forte que TanStack Router.
    - **Não há file-based nativo**.
- **Veredicto**: aceitável, mas TanStack Router ganha no conjunto (tipagem + integração Query).

### Alt 3: Redux Toolkit + RTK Query

- **Prós**: padrão enterprise; DevTools Redux maduras; RTK Query cobre server state.
- **Contras**:
    - **Bundle pesado**: RTK ~12 KB + RTK Query ~9 KB + boilerplate de reducers/slices (~5 KB) = ~26 KB só de state management (vs 14 KB Query + 1 KB Zustand).
    - **Boilerplate**: `createSlice`, `createApi`, `createAsyncThunk` — overhead verbal alto para time pequeno.
    - **Tipagem por generics**: exige mais `as` e inference manual que TanStack Query.
    - **Não há file-based routing**: Redux é só estado; ainda precisaríamos de Router.
- **Veredicto**: over-engineering para o Portal ArtFinal.

### Alt 4: Jotai + TanStack Query + TanStack Router

- **Prós**: atoms granulares; rerender só do consumidor; integração com Query via `atomWithQuery`.
- **Contras**:
    - **Modelo atômico** é elegante mas gera proliferação de atoms em apps médios (50+ atoms globais comuns).
    - **Persist em storage** é plugin externo, menos direto que `persist` do Zustand.
    - **Curva**: pensar "atom" vs "store" é outro paradigma; Zustand é mais mainstream e simples.
- **Veredicto**: pior trade-off de simplicidade vs poder para nosso caso.

### Alt 5: Context API + useReducer (zero libs de state)

- **Prós**: zero deps; padrão React.
- **Contras**:
    - **Performance**: Context API rerender todos os consumidores. Precisa split manual de contextos (boilerplate).
    - **Persistência manual**: sessionStorage requer hook custom.
    - **Sem DevTools**.
- **Veredicto**: aceitável apenas em apps com 2-3 peças de estado. Portal tem 3 stores e crescerá.

## 6. Status

**Accepted.** Congelada para F3-F7. Revisão em F7+ se:

- TanStack Router v1 tiver bug bloqueante não mitigável → migrar para React Router v6.4+ com loaders.
- Stores Zustand explodirem em número (> 10) → avaliar Jotai (atoms) ou slice de Zustand.

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §1 (stack), §4 (TanStack Query), §5 (Router), §6 (Zustand)
- `docs/frontend/05-FRONTEND-SAD.md` §4.1, §5.2, §5.3, §5.4
- ADR-001, ADR-005, ADR-006, ADR-007
