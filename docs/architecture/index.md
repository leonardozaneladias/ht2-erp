---
title: 'Arquitetura — Índice navegável'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# Arquitetura — Índice

> Ponto de entrada para o material de arquitetura do projeto.

## ADRs (Architecture Decision Records)

Formato MADR. Referenciar pelo número: "conforme ADR-0010".

| #    | Título                                                                                                     | Tags                                    |
| ---- | ---------------------------------------------------------------------------------------------------------- | --------------------------------------- |
| 0002 | [Monólito modular Laravel em vez de microservices](./adrs/ADR-0002-monolito-modular.md)                    | arquitetura, topologia, modularidade    |
| 0004 | [ULID público, BIGINT interno no banco](./adrs/ADR-0004-ulid-publico-bigint-interno.md)                    | identidade, modelo-dados                |
| 0005 | [Idempotência em três camadas (request + cache + DB unique)](./adrs/ADR-0005-idempotencia-3-camadas.md)    | idempotencia, concorrencia              |
| 0009 | [Snapshots JSONB imutáveis em entidades transacionais](./adrs/ADR-0009-snapshots-jsonb-imutaveis.md)       | dados, imutabilidade, postgres          |
| 0010 | [Enums PHP backed em todo campo enumerado](./adrs/ADR-0010-enums-php-backed.md)                            | tipagem, enum, dominio                  |
| 0011 | [Horizon + Redis para filas (vs SQS/database)](./adrs/ADR-0011-horizon-redis-filas.md)                     | filas, horizon, redis                   |
| 0012 | [Spatie Permission com `guard_name` explícito por modelo](./adrs/ADR-0012-spatie-permission-guard-name.md) | auth, acl, spatie                       |
| 0014 | [Valores monetários em INTEGER centavos](./adrs/ADR-0014-money-integer-centavos.md)                        | dinheiro, tipagem                       |
| 0015 | [Módulos de negócio como pacotes Composer distribuíveis](./adrs/ADR-0015-modulos-pacotes-composer.md)      | arquitetura, modularidade, distribuição |

## Convenções

- Todos os ADRs seguem MADR (Markdown Architecture Decision Records).
- Todo ADR aprovado é **imutável em essência**: mudanças substantivas criam um novo ADR com `status: superseded_by: ADR-XXXX`.
- Referência cruzada é feita por número (ADR-0010).

## Ligações externas

- `CLAUDE.md` (raiz) — convenções de projeto para agentes e devs
