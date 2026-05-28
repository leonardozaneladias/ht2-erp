---
title: 'ADR-002: SPA consome exclusivamente /api/v1 (sem endpoints internos)'
adr: 002
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - api
    - contrato
    - versionamento
    - mobile
---

# ADR-002: SPA consome exclusivamente `/api/v1` (sem endpoints internos)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, api, contrato, versionamento, mobile

## 1. Contexto

O Portal ArtFinal v2 tem, no mínimo, **três clientes** que consomem domínio:

1. SPA React web (este documento).
2. App React Native mobile (F8).
3. Admin Livewire (consome diretamente models/actions no mesmo processo — fora do escopo deste ADR).

A pergunta de arquitetura: **o SPA e o mobile consomem o mesmo contrato HTTP?**

A tentação de criar endpoints específicos para SPA ("BFF — Backend-for-Frontend") é real:

- SPA pode querer resposta moldada para telas específicas (ex.: `GET /portal/home-bundle` com dados de dashboard agregados).
- SPA pode evitar over-fetching com payloads customizados.
- BFF separa domínios diferentes de leitura (ex.: app de pedidos vs app de entregas).

Mas o Portal ArtFinal tem características que tornam BFF um anti-padrão aqui:

- **Domínio único**: formando consome os mesmos dados de adesão, pagamento, convites, seating independentemente do canal.
- **Equipe pequena**: manter dois contratos (v1 mobile + v1-spa web) dobra esforço de CRUD, resources, testes e docs.
- **API-first já decidida no backend** (ADR-0001 backend): `api/v1` é fonte-de-verdade com OpenAPI publicada.

## 2. Decisão

**O SPA React consome exclusivamente `/api/v1`, o mesmo contrato consumido pelo app mobile F8 e por integrações externas.** Nenhum endpoint "interno", "bundle-para-home" ou "otimizado para SPA" existe. Necessidades de moldagem de resposta são atendidas via:

- **Sparse fields / conditional fields** em Resources (`$this->when()`, `$this->whenLoaded()`).
- **Query params padronizados** (`?include=parcelas,convites`) — suportado por Resources.
- **Cursor pagination** (não offset) via `cursor` query param, `next_cursor` em `meta`.
- **Codegen OpenAPI → TypeScript** (ADR-005) garante que qualquer mudança no contrato reflita tipada no SPA.

Quando o SPA precisa de dados agregados (ex.: dashboard home), a resposta é produzida por **Actions + Resources reutilizáveis**, não por rotas específicas. Se a Home do SPA é `GET /api/v1/me/dashboard`, o mobile chama exatamente a mesma rota.

## 3. Consequências

### Positivas

- **Zero duplicação de código de domínio**: Actions/Services são fonte única; Controllers `V1` são finos.
- **Contrato estável e testável**: suite de contract tests (Pest + Spectator) valida que nenhum PR quebra `api/v1`.
- **Evolução disciplinada**: campo novo → adiciona via `$this->when()` em Resource (compatível); breaking change → `api/v2` (ADR-0001 backend) com Scramble.
- **Onboarding simplificado**: um engenheiro novo precisa aprender apenas `docs/api/openapi-skeleton.yaml`; SPA e mobile são tradução direta.
- **Auditoria unificada**: todo acesso a domínio passa pela mesma camada HTTP — métricas, rate limiting, logs, tracing uniformes.
- **Integrações externas** (futuro: ERP, parceiros) consomem **a mesma API** sem plataforma dedicada.

### Negativas

- **Potencial over-fetching**: SPA pode receber campos que só o mobile usa (e vice-versa). Mitigação: Resources com conditional fields + documentação de uso.
- **Latência em telas com dados agregados**: home com 3 seções pode exigir 3 GETs. Mitigação: Action dedicada + Resource de bundle (ex.: `MeResource` inclui `parcelas`, `convites`, `mesas_reservadas` via `include` query param).
- **Acoplamento de evolução**: mudar `api/v1` exige coordenação com clientes existentes. Mitigação: política de deprecação RFC 8594 (ADR-0001 backend).
- **Mobile F8 pode precisar de campos que SPA web não usa** (ex.: `avatar_url_low_res` para listas lentas). Solução: `include`-opt-in, não novo endpoint.

## 4. Trade-offs

| Ganhamos                                                 | Perdemos                                                           |
| -------------------------------------------------------- | ------------------------------------------------------------------ |
| Um contrato, uma OpenAPI, um codegen, uma doc            | Flexibilidade de moldagem extrema para cada cliente                |
| Mobile F8 "grátis" em termos de endpoints                | Potencial over-fetching em alguns cenários                         |
| Testes de contrato simplificados (um único spec)         | Mudanças de contrato exigem disciplina (não dá pra "mexer rápido") |
| Integrações futuras (ERP, parceiros) consomem mesma base | Não é possível "quebrar" o contrato para otimização local          |
| Alinhamento total com ADR-0001 backend                   | Exige cultura de versionamento e deprecação                        |

## 5. Alternativas rejeitadas

### Alt 1: BFF (Backend-for-Frontend) dedicado ao SPA

- **Prós**: respostas moldadas sob medida; zero over-fetching; flexibilidade extrema.
- **Contras**:
    - **Dobra a superfície de manutenção**: dois contratos, duas OpenAPIs, dois codegens.
    - **Duplica lógica de domínio** ou exige uma camada intermediária (BFF proxy) que adiciona latência.
    - **Equipe pequena não sustenta**: o mesmo dev escreveria o endpoint mobile **e** o endpoint SPA para o mesmo caso de uso.
    - **Viola ADR-0001 backend** (API-first único).

### Alt 2: GraphQL

- **Prós**: cliente define forma da resposta; zero over/under-fetching; tipagem forte via codegen.
- **Contras**:
    - **Complexidade operacional**: schema, resolvers, N+1 subjacente (dataloader obrigatório), cache mais difícil.
    - **Curva de aprendizado**: nem backend Laravel nem frontend React têm profundidade em GraphQL.
    - **Ecossistema Laravel + GraphQL** é maduro mas pequeno (Lighthouse, Hotwired). Sairia do caminho "happy path" de Scramble + openapi-typescript.
    - **Mobile F8 em Expo**: Apollo Client RN é viável mas mais pesado (~60 KB gzip).
    - **Over-engineering para MVP**: não há caso de uso hoje que GraphQL resolva e REST+sparse fields não resolva.
- Pode ser reavaliada em F8+ se integrações com parceiros justificarem (ADR futuro).

### Alt 3: REST puro, mas endpoints separados para SPA (ex.: `/api/spa/*`)

- **Prós**: liberdade de moldagem; não conflita com mobile.
- **Contras**:
    - **Semanticamente idêntico a um BFF**, com os mesmos contras.
    - **Obriga decidir caso a caso** o que vai para `v1` e o que vai para `spa`: fronteira difusa, gera retrabalho.
    - **Não há caso real** no MVP onde `api/v1` com `include`/`fields` não resolva.

### Alt 4: tRPC

- **Prós**: tipagem fim-a-fim automática entre TS frontend e backend TS.
- **Contras**:
    - **Backend é Laravel (PHP)**: tRPC é TS-to-TS, incompatível.
    - **Não resolve mobile nativo** sem Nitro/React Native adapter.
    - **Fora da stack**.

## 6. Status

**Accepted.** Decisão alinhada com ADR-0001 do backend, congelada até F8.

Exceções autorizadas (não violam a ADR):

- Rotas **não** `/api/v1` que são **infraestruturais**, não de domínio: `/sanctum/csrf-cookie`, `/up` (healthcheck), `/broadcasting/auth` (se Reverb entrar em F7+).

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §0 item 2, §3 (Axios client), §13 (codegen)
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §0 item 2, §1.2, §2.1
- `docs/architecture/SAD-arc42.md` §1.2 objetivo G1
- `docs/frontend/05-FRONTEND-SAD.md` §1.2 FG2, §3.3, §3.4
- `docs/api/openapi-skeleton.yaml` — contrato
- ADR-001 (SPA React puro), ADR-005 (codegen OpenAPI), ADR-008 (Sanctum)
- Backend: ADR-0001 (API-first `api/v1`), ADR-0007 (OpenAPI via Scramble)
