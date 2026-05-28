---
title: 'ADR-005: openapi-typescript como fonte única de tipos de contrato da API'
adr: 005
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - codegen
    - typescript
    - openapi
    - ci
    - contrato
---

# ADR-005: `openapi-typescript` como fonte única de tipos de contrato da API

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, codegen, typescript, openapi, ci, contrato

## 1. Contexto

O SPA consome `/api/v1` (ADR-002). O backend publica OpenAPI via Scramble (ADR-0007 backend) em `docs/api/openapi-skeleton.yaml`. O SPA é TypeScript estrito (§SAD §2.2).

Questão: **como garantir que os tipos TS usados no SPA refletem exatamente o contrato publicado pelo backend, sem drift?**

Opções gerais:

1. **Tipar manualmente** cada DTO no SPA (`type FormandoMe = { ... }`).
2. **Gerar tipos automaticamente** a partir de OpenAPI.
3. **Gerar tipos + hooks** automaticamente (orval, openapi-generator).

A equipe é pequena (1-2 devs frontend até F7). Qualquer disciplina manual de sincronização é frágil — basta uma alteração esquecida no backend para o SPA começar a receber respostas com campos não tipados (ou cair na branch `any`). Esse é exatamente o risco R5 do SAD (§11).

## 2. Decisão

**O SPA usa `openapi-typescript` v7 como ferramenta exclusiva de geração de tipos a partir de `docs/api/openapi-skeleton.yaml`.** O output é `resources/spa/src/api/types.gen.ts`, **tratado como read-only** (é reescrito integralmente a cada geração).

Regras operacionais:

- **Nunca** editar `types.gen.ts` manualmente.
- **Nunca** criar `type` ou `interface` em código aplicação que duplique DTO da API. Usar `components['schemas']['Formando']` do arquivo gerado.
- **CI enforcement**: step obrigatório em `.github/workflows/ci.yml` (antes de lint/test/build) que:
    1. Executa `npx openapi-typescript docs/api/openapi-skeleton.yaml -o resources/spa/src/api/types.gen.ts`
    2. Executa `git diff --exit-code resources/spa/src/api/types.gen.ts`
    3. Se há diff, **falha o build** com mensagem "Regenere types.gen.ts — OpenAPI e tipos estão dessincronizados".
- **Pré-commit hook** (Husky) replica o mesmo check localmente (opcional, best-effort).
- Quando o backend atualiza contrato, fluxo é:
    1. Backend merge-a a mudança de `openapi-skeleton.yaml`.
    2. SPA regenera `types.gen.ts`.
    3. TS compiler aponta callsites afetados.
    4. SPA ajusta e faz merge.

Types helpers no SPA (`api/hooks/*`) fazem **re-export seletivo**:

```typescript
// api/hooks/use-adesao.ts
import type { components } from '@/api/types.gen';

export type Adesao = components['schemas']['Adesao'];
export type AdesaoList = components['schemas']['AdesaoList'];
```

Isso dá uma camada fina de alias para manter callsites limpos.

## 3. Consequências

### Positivas

- **Zero drift entre contrato e tipos**. Impossível o SPA compilar contra um contrato antigo após o merge de uma atualização backend.
- **Type safety fim-a-fim**: TS aponta exatamente onde um DTO mudou. Refactor seguro.
- **Codegen barato**: `openapi-typescript` é puro TS, executa em ~200 ms para OpenAPI médio. Zero infra extra.
- **Output legível**: tipos inline em um arquivo único, sem classes nem runtime. Fácil de auditar o que o SPA realmente "vê".
- **Integração natural com TanStack Query**: hooks tipados em `api/hooks/` consomem `components['schemas']` como return type.
- **Facilita revisão de PR**: uma mudança de contrato aparece como diff em `types.gen.ts`, tornando o impacto visível.
- **Compatível com Scramble** (backend ADR-0007): Scramble gera OpenAPI automático a partir de rotas + Resources + FormRequests Laravel — gera input, `openapi-typescript` consome.

### Negativas

- **Tipos são "puros"**: não há runtime validation incluída. Uma resposta com shape errado em runtime passa pelo TS. Mitigação: Zod schemas opcionais em operações críticas (login, pagamento, wizard final).
- **Dependência de Scramble estar atualizado**: se Scramble não capturar um campo específico, o codegen também não. Mitigação: testes de contrato (Pest + Spectator) + revisão manual do openapi-skeleton.yaml.
- **Regeneração esquecida localmente** quebra no CI. Mitigação: Husky pre-commit + README claro.
- **Nomes gerados por Scramble/OpenAPI** podem ficar verbosos (`components['schemas']['App\\Http\\Api\\V1\\Resources\\FormandoResource']`). Mitigação: Scramble configurado para nomes limpos; re-export aliases em hooks.

## 4. Trade-offs

| Ganhamos                                                            | Perdemos                                                          |
| ------------------------------------------------------------------- | ----------------------------------------------------------------- |
| Zero drift contrato ↔ tipos                                        | Dependência de Scramble estar correto e atualizado                |
| TS ponta-a-ponta refatoração segura                                 | Regeneração virou etapa obrigatória (sem atalho)                  |
| CI impede merge com tipos fora de sincronia                         | Nomes gerados exigem aliases de re-export em alguns casos         |
| Zero runtime overhead (só tipos, não hooks)                         | Sem validação runtime automática (precisamos de Zod onde crítico) |
| Ferramenta estável, TS-first, mantida pela comunidade TanStack/Drew | Curva de "entender o objeto `components`" (~30 min)               |

## 5. Alternativas rejeitadas

### Alt 1: Tipos manuais (duplicar DTOs no SPA)

- **Prós**: controle local total; nomes amigáveis.
- **Contras**:
    - **Drift inevitável** em equipe pequena. Hoje SPA define `{ id: string }`, amanhã backend muda para `{ ulid: string }` e ninguém percebe até erro 500.
    - **Duplicação** multiplicada por dezenas de DTOs.
    - **Viola FG2** do SAD (zero drift contrato ↔ tipos).

### Alt 2: `orval`

- **Prós**: gera **tipos + hooks** (axios, tanstack-query, SWR). Documentação farta.
- **Contras**:
    - **Gera hooks** — conflita com nossa convenção de `api/hooks/*` escritos à mão (queryKeys convencionados, integração com `authStore`, polling customizado).
    - **Config pesada**: `orval.config.ts` com transformers, operações por tag, output por módulo. Mais cerimônia.
    - **Gera um hook por operação**: para seating, isso quer dizer `useCreateMesaReserva`, `useConfirmMesaReserva`, `useLiberarMesaReserva` — perde coerência com os nossos stores (`holdStore`, `wizardStore`).
    - **Tamanho de output**: arquivos gerados ficam grandes (dezenas de milhares de linhas), CI mais lento.
- **Veredicto**: excelente se fôssemos fazer "só CRUD"; perde para a abordagem "tipos puros + hooks manuais finos".

### Alt 3: `openapi-generator-cli` (Java)

- **Prós**: maduro; suporta 50+ linguagens; usado por grandes empresas.
- **Contras**:
    - **JVM dependency**: CI precisa Java instalado. Slow build step.
    - **Output verboso**: gera classes TS com runtime. Bundle maior.
    - **Configuração por template**: curva de aprendizado para customizar.

### Alt 4: `swagger-typescript-api`

- **Prós**: similar ao `openapi-typescript`, com opção de gerar clients.
- **Contras**:
    - **Mantido por um único dev** (risco de abandono).
    - **Output mistura tipos + client**: queremos separar.
    - `openapi-typescript` tem suporte mais ativo e é mantido junto com `openapi-fetch` (mesma autor).

### Alt 5: tRPC (tipagem fim-a-fim sem OpenAPI)

- **Prós**: zero codegen; tipos TS fluem naturalmente do backend para frontend.
- **Contras**:
    - **Backend é Laravel (PHP)**: tRPC é TS-to-TS, **incompatível** com a stack.
    - **Viola arquitetura** (REST HTTP + OpenAPI é a base).

### Alt 6: GraphQL Code Generator (graphql-codegen)

- **Prós**: gera tipos a partir de schema GraphQL.
- **Contras**:
    - **Não temos GraphQL** (ADR-002 rejeitou GraphQL).
    - Irrelevante para nossa arquitetura.

## 6. Status

**Accepted.** Congelada. Revisão apenas se:

- `openapi-typescript` v7 for descontinuado (improvável — ativo e Vercel-backed).
- Decidirmos gerar hooks automaticamente em F8+ (mas provavelmente orval mesmo assim).

Checklist operacional:

- [ ] Script `npm run gen:api` em `package.json` do SPA.
- [ ] Step CI `check-openapi-types` antes de lint/test/build.
- [ ] Husky pre-commit roda o mesmo check.
- [ ] README do SPA documenta "como regenerar tipos quando o contrato muda".

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §0 item 10, §13 (codegen)
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2.12 (OpenAPI)
- `docs/architecture/adrs/ADR-0007-openapi-scramble.md` — gerador backend
- `docs/frontend/05-FRONTEND-SAD.md` §1.2 FG2, §11 R5
- ADR-001, ADR-002, ADR-004
