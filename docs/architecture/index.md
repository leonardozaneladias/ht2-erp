---
title: 'Arquitetura — Índice navegável'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# Arquitetura do Portal ArtFinal v2 — Índice

> Ponto de entrada para todo material de arquitetura do backend API v1. Última atualização: 2026-04-18.

## Documento principal (arc42)

| Documento                        | Descrição                                                                                       |
| -------------------------------- | ----------------------------------------------------------------------------------------------- |
| [`SAD-arc42.md`](./SAD-arc42.md) | Software Architecture Document completo (arc42): contexto, blocos, runtime, decisões, qualidade |

## ADRs (Architecture Decision Records)

Formato MADR. Referenciar pelo número: "conforme ADR-0006".

| #    | Título                                                                                                           | Tags                                 |
| ---- | ---------------------------------------------------------------------------------------------------------------- | ------------------------------------ |
| 0001 | [API-first com prefixo `api/v1` desde o dia 1](./adrs/ADR-0001-api-first-versionamento-v1.md)                    | api, versionamento, http, contrato   |
| 0002 | [Monólito modular Laravel em vez de microservices](./adrs/ADR-0002-monolito-modular.md)                          | arquitetura, topologia, modularidade |
| 0003 | [Laravel Sanctum em modo dual (SPA stateful + token mobile)](./adrs/ADR-0003-sanctum-dual-mode.md)               | auth, sanctum, spa, mobile           |
| 0004 | [ULID público na API, BIGINT interno no banco](./adrs/ADR-0004-ulid-publico-bigint-interno.md)                   | identidade, modelo-dados, api        |
| 0005 | [Idempotência em três camadas (header + cache + DB unique)](./adrs/ADR-0005-idempotencia-3-camadas.md)           | idempotencia, concorrencia           |
| 0006 | [Concorrência em seating (Redis lock + unique parcial + lockForUpdate)](./adrs/ADR-0006-concorrencia-seating.md) | concorrencia, seating, postgres      |
| 0007 | [OpenAPI gerado por Scramble (zero-anotação)](./adrs/ADR-0007-openapi-scramble.md)                               | documentacao, openapi, api-tooling   |
| 0008 | [Verbos em URL para transições de state-machine](./adrs/ADR-0008-verbos-state-machine.md)                        | api, rest, state-machine             |
| 0009 | [Snapshots JSONB imutáveis em entidades transacionais](./adrs/ADR-0009-snapshots-jsonb-imutaveis.md)             | dados, imutabilidade, postgres, lgpd |
| 0010 | [Enums PHP 8.1+ backed em todo campo enumerado](./adrs/ADR-0010-enums-php-backed.md)                             | tipagem, enum, dominio               |
| 0011 | [Horizon + Redis para filas (vs SQS/database)](./adrs/ADR-0011-horizon-redis-filas.md)                           | filas, horizon, redis                |
| 0012 | [Spatie Permission com `guard_name` explícito por modelo](./adrs/ADR-0012-spatie-permission-guard-name.md)       | auth, acl, spatie                    |
| 0013 | [Webhook de pagamento — HMAC + idempotência + job pós-commit](./adrs/ADR-0013-webhook-hmac-idempotencia.md)      | webhook, pagamentos, hmac            |
| 0014 | [Valores monetários em INTEGER centavos](./adrs/ADR-0014-money-integer-centavos.md)                              | dinheiro, tipagem                    |

## Technical Designs

Detalhamento por bounded context com diagramas Mermaid.

| Documento                                                              | Bounded context | Foco                                                           |
| ---------------------------------------------------------------------- | --------------- | -------------------------------------------------------------- |
| [`technical-design-seating.md`](./technical-design-seating.md)         | Seating         | Reserva, confirmação, troca, expiração; 4 camadas concorrência |
| [`technical-design-payments.md`](./technical-design-payments.md)       | Pagamentos      | Intent → webhook → job → reconciliação; driver pattern Saloon  |
| [`technical-design-invitations.md`](./technical-design-invitations.md) | Convites + Rsvp | Token criptográfico, lote assíncrono, CotaCalculator, RSVP     |
| [`technical-design-extras.md`](./technical-design-extras.md)           | Extras          | Pedido → aprovação → pagamento → emissão derivada; snapshot    |

## Mapa de leitura recomendado

1. **Comece pelo SAD** (`SAD-arc42.md`) para entender o todo.
2. **Leia ADR-0001, ADR-0002, ADR-0004** para fundamentos de contrato e topologia.
3. **Para concorrência**: ADR-0005 + ADR-0006 + `technical-design-seating.md`.
4. **Para pagamentos**: ADR-0013 + ADR-0014 + `technical-design-payments.md`.
5. **Para convites**: ADR-0009 + `technical-design-invitations.md`.
6. **Para extras (depende de pagamentos e convites)**: `technical-design-extras.md`.
7. **Para segurança/ACL**: ADR-0003 + ADR-0012.
8. **Para observabilidade e ops**: ADR-0007 + ADR-0011.

## Convenções

- Todos os ADRs seguem MADR (Markdown Architecture Decision Records).
- Todo ADR aprovado é **imutável em essência**: mudanças substantivas criam um novo ADR com `status: superseded_by: ADR-XXXX`.
- Todo technical design pode ser ampliado quando o domínio evoluir — versionamento por `version:` no frontmatter.
- Referência cruzada é feita por número (ADR-0006) e por nome curto (technical-design-seating).

## Ligações externas

- [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) — planejamento técnico executável do backend
- [`PRD_v4.md`](../prd/PRD_v4.md) — requisitos de produto
- `CLAUDE.md` (raiz) — convenções de projeto para agentes e devs
