---
title: 'Arquitetura — Índice navegável'
version: 1.1.0
date: 2026-10-25
status: accepted
---

# Arquitetura — Índice

> Ponto de entrada para o material de arquitetura do projeto.

## ADRs (Architecture Decision Records)

Formato MADR. Referenciar pelo número: "conforme ADR-0010".

| #    | Título                                                                                                                                       | Status                                                         | Tags                                          |
| ---- | -------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- | --------------------------------------------- |
| 0002 | [Monólito modular Laravel em vez de microservices](./adrs/ADR-0002-monolito-modular.md)                                                      | aceito                                                         | arquitetura, topologia, modularidade          |
| 0004 | [ULID público, BIGINT interno no banco](./adrs/ADR-0004-ulid-publico-bigint-interno.md)                                                      | aceito, ainda sem aplicação no código¹                         | identidade, modelo-dados                      |
| 0005 | [Idempotência em três camadas (request + cache + DB unique)](./adrs/ADR-0005-idempotencia-3-camadas.md)                                      | aceito, ainda sem aplicação no código¹                         | idempotencia, concorrencia                    |
| 0009 | [Snapshots JSONB imutáveis em entidades transacionais](./adrs/ADR-0009-snapshots-jsonb-imutaveis.md)                                         | aceito, ainda sem aplicação no código¹                         | dados, imutabilidade, postgres                |
| 0010 | [Enums PHP backed em todo campo enumerado](./adrs/ADR-0010-enums-php-backed.md)                                                              | aceito                                                         | tipagem, enum, dominio                        |
| 0011 | [Horizon + Redis para filas (vs SQS/database)](./adrs/ADR-0011-horizon-redis-filas.md)                                                       | aceito                                                         | filas, horizon, redis                         |
| 0012 | [Spatie Permission com `guard_name` explícito por modelo](./adrs/ADR-0012-spatie-permission-guard-name.md)                                   | aceito                                                         | auth, acl, spatie                             |
| 0014 | [Valores monetários em INTEGER centavos](./adrs/ADR-0014-money-integer-centavos.md)                                                          | aceito                                                         | dinheiro, tipagem                             |
| 0015 | [Módulos de negócio como pacotes Composer distribuíveis](./adrs/ADR-0015-modulos-pacotes-composer.md)                                        | aceito; refinado por 0016 e 0021, completado por 0022²        | arquitetura, modularidade, distribuição       |
| 0016 | [Instâncias por cliente via clone + re-origin](./adrs/ADR-0016-instancias-por-cliente.md)                                                    | aceito; escopo estreitado por 0017 e 0019²                     | arquitetura, distribuição, instâncias         |
| 0017 | [Produto novo nasce do skeleton via Composer](./adrs/ADR-0017-produto-novo-via-skeleton.md)                                                  | aceito                                                         | arquitetura, distribuição, composer           |
| 0018 | [Multiempresa no core, atrás de um modo single-tenant](./adrs/ADR-0018-multiempresa-no-core-modo-single-tenant.md)                           | aceito                                                         | arquitetura, multiempresa, core               |
| 0019 | [A plataforma é abstrata — nenhum produto vive dentro dela](./adrs/ADR-0019-plataforma-abstrata-sem-produto.md)                              | aceito                                                         | arquitetura, plataforma, fronteira            |
| 0020 | [Catálogos de uso universal ficam no core; só o de domínio vira extensão](./adrs/ADR-0020-catalogos-universais-no-core.md)                   | aceito                                                         | arquitetura, plataforma, fronteira, catálogos |
| 0021 | [Módulo, recurso, área de acesso e seção de menu — quatro nomes, quatro coisas](./adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)      | aceito                                                         | arquitetura, plataforma, vocabulário, extensibilidade |
| 0022 | [A dependência entre core e extensão é de mão única](./adrs/ADR-0022-dependencia-de-mao-unica.md)                                            | aceito                                                         | arquitetura, plataforma, fronteira, dependência |
| 0023 | [API REST é extensão — canal no core, Sanctum e contrato na extensao-api](./adrs/ADR-0023-api-rest-como-extensao.md)                         | aceito                                                         | arquitetura, fronteira, api, sanctum, openapi |
| 0024 | [IA é uma extensão de contratos, com fakes ligados por padrão](./adrs/ADR-0024-ia-como-extensao-de-contratos.md)                             | aceito                                                         | arquitetura, fronteira, ia, contratos         |

¹ **Aceito, ainda sem aplicação no código.** Em 2026-10-25 não há ULID, cabeçalho `Idempotency-Key` nem coluna JSONB no código PHP de `app/`, `packages/`, `database/`, `config/` e `routes/` (busca sem distinção de caixa por `ulid`, `idempotency` e `jsonb`; a única ocorrência é um nome de produto num CSV de NCM). As decisões vieram do produto que deu origem à plataforma e valem para quando a necessidade aparecer. Antes de aplicar uma delas, releia o ADR: ele foi escrito para aquele domínio.

² **Refinado, não revogado.** O texto original do ADR-0015 fala em `packages/modulo-*`, `erp-module-{slug}`, `make:modulo --module=` e consumo por Composer "depois". Hoje os pacotes são `packages/extensao-*`, publicados como `<org>/ht2ml-*`; o gerador é `make:modulo {chave}` + `make:recurso --modulo=`; e o consumo por Composer já existe. O ADR-0016 vale para instâncias de um produto, não para produtos novos (que nascem do skeleton, ADR-0017), e o produto não é mais este repositório (ADR-0019). Leia o ADR original junto com os que o refinam.

## Números retirados

Estes números existiram e foram retirados no commit `8a8b58b` (2026-05-29, "removendo domínio ArtFinal"), junto com o produto de onde vinham. Não reutilize os números. O texto continua no histórico: `git show 8a8b58b^:docs/architecture/adrs/<arquivo>`.

| #    | Título                                                                      | Arquivo                                     | Onde a ideia foi parar                                                      |
| ---- | --------------------------------------------------------------------------- | ------------------------------------------- | --------------------------------------------------------------------------- |
| 0001 | API-first com prefixo `api/v1` desde o dia 1                                | `ADR-0001-api-first-versionamento-v1.md`    | ADR-0023 (a versão fica com o módulo)                                       |
| 0003 | Laravel Sanctum em modo dual (SPA stateful + token mobile)                  | `ADR-0003-sanctum-dual-mode.md`             | ADR-0023 (só o modo token)                                                  |
| 0006 | Concorrência em seating (Redis lock + unique parcial + lockForUpdate)       | `ADR-0006-concorrencia-seating.md`          | —                                                                           |
| 0007 | OpenAPI gerado por Scramble (zero-anotação)                                 | `ADR-0007-openapi-scramble.md`              | ADR-0023 (`api:openapi --verificar`)                                        |
| 0008 | Verbos em URL para transições de state-machine                              | `ADR-0008-verbos-state-machine.md`          | —                                                                           |
| 0013 | Webhook de pagamento — HMAC + idempotência + job pós-commit                 | `ADR-0013-webhook-hmac-idempotencia.md`     | ADR-0023 (HMAC no core, middleware `webhook.assinatura`)                    |

## Convenções

- Todos os ADRs seguem MADR (Markdown Architecture Decision Records).
- Todo ADR aprovado é **imutável em essência**: mudanças substantivas criam um novo ADR com `status: superseded_by: ADR-XXXX`. Refinamentos que não revogam entram como addendum no próprio ADR e como nota na coluna de status acima.
- Referência cruzada é feita por número (ADR-0010).
- ADRs de um módulo ficam com o módulo (os do RH em [`../plans/modules/rh/adrs/`](../plans/modules/rh/adrs/)).

## Ligações externas

- [`CLAUDE.md`](../../CLAUDE.md) (raiz) — convenções de projeto para agentes e devs
- [`CONTEXT-MAP.md`](../../CONTEXT-MAP.md) (raiz) — glossário da plataforma
