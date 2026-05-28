---
title: 'ADR-0004: ULID público na API, BIGINT interno no banco'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0004: ULID público na API, BIGINT interno no banco

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Segurança | **Tags:** identidade, modelo-dados, api

## Contexto e problema

Toda entidade transacional pode ser referenciada em URL pública (`/api/v1/eventos/{id}/convites/{id}`), e-mails, webhooks externos, links de convite. Usar o `id BIGINT` auto-increment em URL expõe o volume ("somos o convite #7"), facilita enumeração (IDOR probing) e cria acoplamento entre contratos externos e detalhe interno de implementação.

Por outro lado, índices compostos, FKs e joins internos rodam muito melhor em `BIGINT` do que em `CHAR(26)`.

## Drivers da decisão

- Segurança: ocultar volume e cadência do sistema.
- Privacidade: convite/reserva/pagamento são recursos sensíveis.
- Performance: FKs e índices em BIGINT são ~4× menores que em CHAR(26).
- DX: ULID é ordenável (lexicográfico = temporal), legível, gera-se client-side se preciso.
- Compatibilidade com Scramble e route-model-binding.

## Alternativas consideradas

### Alt 1: UUID v4 público + BIGINT interno

- Prós: ubíquo, suporte amplo.
- Contras: 36 chars, não ordenável; prejudica locality em índices cobertos; e URL feia.

### Alt 2: BIGINT direto em URL

- Prós: performance máxima.
- Contras: vazamento de volume, IDOR, acoplamento contratual.

### Alt 3: ULID em URL + BIGINT interno (escolhida)

- Prós: 26 chars, lexicográfico = temporal, alphabet Crockford Base32, colisão praticamente nula; FKs seguem BIGINT sem degradação; Laravel route-model-binding suporta via `{convite:ulid}`.
- Contras: coluna extra `ulid CHAR(26) UNIQUE` em toda tabela exposta; lookup por ULID exige índice único.

## Decisão

Toda tabela com recurso exposto externamente tem:

- `id BIGSERIAL PRIMARY KEY` (interno, FKs, joins).
- `ulid CHAR(26) UNIQUE NOT NULL` (público).
- Trait `App\Support\Concerns\HasUlid` atribui automaticamente no `creating` e expõe `getRouteKeyName(): 'ulid'`.

Rotas usam binding explícito `{convite:ulid}`. Resources sempre retornam `id => $this->ulid` (nunca `$this->id`). Jobs e Events continuam carregando `id BIGINT` internamente por performance.

FKs internas são sempre `BIGINT` → `BIGINT`.

## Consequências positivas

- URLs não vazam volume nem cadência.
- Tentativas de enumeração precisam adivinhar 26 chars Base32 (128 bits de entropia útil).
- ULID ordenável por tempo facilita auditoria e debugging (logs vêm em ordem cronológica natural).
- Performance interna preservada (FKs BIGINT).

## Consequências negativas

- Todas as tabelas expostas ganham coluna `ulid` com índice único (custo aceitável).
- Dev precisa lembrar de nunca expor `$this->id` em Resource. Mitigação: Arch test Pest que proíbe `$this->id` em `App\Http\Api\V1\Resources\*`.

## Ligações

- §0 princípio 8, §2.7, §4.1, §4.3, §4.4 do PLANEJAMENTO_BACKEND_APIV1.md
- Apêndice D anti-pattern #4
- ADR-0001 (API-first), ADR-0013 (webhooks)
- SAD arc42 seção "Conceitos de corte transversal — Identidade"
