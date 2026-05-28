---
title: 'ADR-0008: Verbos em URL para transições de state-machine'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0008: Verbos em URL para transições de state-machine

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Arquitetura | **Tags:** api, rest, state-machine

## Contexto e problema

Vários recursos do domínio têm máquina de estados explícita: reserva (`hold → confirmada → cancelada/expirada`), pedido extra (`rascunho → aprovado → pago → estornado`), adesão (`pendente_pagamento → ativa → cancelada`), convite (`emitido → confirmado → cancelado/inutilizado`), enquete (`rascunho → aberta → encerrada`). Como expressar transições via HTTP sem permitir ao cliente setar qualquer estado livremente?

Uma aparência "REST puro" (só substantivos) força `PATCH /reservas/{id}` com body `{status: 'confirmada'}` — o que entrega a máquina de estados para o cliente.

## Drivers da decisão

- Cliente não deve poder escolher transição arbitrária.
- Cada transição pode ter invariantes diferentes (validar hold expirado, cota, saldo, etc.).
- Clareza em logs, spec OpenAPI e observabilidade ("qual endpoint foi chamado?").
- Alinhamento com padrão de mercado (Stripe, GitHub, Atlassian).

## Alternativas consideradas

### Alt 1: `PATCH /reservas/{id}` com `{status: "confirmada"}`

- Prós: REST "puro".
- Contras: cliente pode tentar setar qualquer status; servidor precisa transcrever cada valor em lógica diferente; mistura PATCH "mudar observação" com "transição crítica"; perde clareza.

### Alt 2: Sub-recurso plural `POST /reservas/{id}/confirmations`

- Prós: REST "purista".
- Contras: em PT-BR fica estranho (`/confirmacoes`); não agrega valor; endpoints raramente têm estado próprio.

### Alt 3: Verbo em URL `POST /reservas/{id}/confirmar` (escolhida)

- Prós: precedente forte (Stripe `/capture`, `/cancel`, `/refund`; GitHub `/merge`, `/dismiss`); cada transição vira endpoint independente com FormRequest, Policy, observabilidade dedicados; cliente **não pode inventar** transições; spec OpenAPI mostra exatamente as ações possíveis.
- Contras: viola o princípio "REST = só substantivos". Trade-off aceito.

## Decisão

Transições de state-machine usam `POST /<recurso>/{id}/<verbo>` com FormRequest e Policy dedicados. Lista canônica no MVP (§2.15 do planejamento):

| Recurso          | Ações verbo-permitidas                             |
| ---------------- | -------------------------------------------------- |
| `reservas`       | `confirmar`, `trocar`, `cancelar`                  |
| `pedidos-extras` | `aprovar`, `cancelar`, `estornar`                  |
| `adesoes`        | `confirmar`, `cancelar`                            |
| `convites`       | `reemitir`, `transferir`, `cancelar`               |
| `enquetes`       | `publicar`, `encerrar`                             |
| `pagamentos`     | `consultar` (GET-like via POST p/ idempotency key) |

Qualquer ação fora da tabela exige atualizar §2.15 antes do merge (enforceable no PR review).

CRUD puro (criar recurso, atualizar campos não-estado, deletar) **continua sem verbo**: `POST /reservas`, `PATCH /reservas/{id}` (só campos livres), `DELETE /reservas/{id}` (cancelamento soft via estado).

Resources expõem as ações disponíveis via HATEOAS (`links.confirmar`, `links.cancelar`) baseado no estado atual — `null` quando indisponível (§2.5).

## Consequências positivas

- Máquina de estados fica no servidor; cliente só pode pedir transições declaradas.
- FormRequest + Policy por ação → autorização e validação precisas.
- Logs legíveis ("endpoint `reservas.confirmar` chamado 3× em 5min").
- Clients gerados (orval) expõem métodos tipados (`confirmarReserva(id)`) — DX superior a `patchReserva(id, { status })`.

## Consequências negativas

- Mais rotas para manter (1 por ação vs 1 PATCH genérico). Aceito — cada ação tem invariantes distintas.
- Viola ortodoxia REST; documentar escolha é necessário (este ADR cumpre esse papel).

## Ligações

- §2.15 do PLANEJAMENTO_BACKEND_APIV1.md
- §2.2 (rotas), §2.5 (HATEOAS), §2.13 (status codes por padrão)
- ADR-0001 (API-first), ADR-0010 (enums)
- SAD arc42 seção "Visão de blocos — Controllers"
