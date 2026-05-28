---
title: 'ADR-0002: Monólito modular Laravel em vez de microservices'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0002: Monólito modular Laravel em vez de microservices

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Arquitetura | **Tags:** arquitetura, topologia, modularidade

## Contexto e problema

O Portal ArtFinal tem domínio coeso (adesão → convite → RSVP → seating → extras → pagamento) com fortes invariantes transacionais entre contextos. O time fundador é pequeno (engenharia core < 6 devs) e o SLA alvo é 99,5% (PLANEJAMENTO §Apêndice B pergunta 5).

A decisão em aberto é: começar com microservices (um serviço por bounded context) ou monólito modular Laravel 13 com fronteiras internas explícitas?

## Drivers da decisão

- Custo operacional de infra e observabilidade distribuída vs tamanho do time.
- Transações cross-context (ex.: `ConfirmarPagamentoExtraAction` → `EmitirLoteConvitesAction`) precisam ser atômicas no MVP.
- Time-to-market de F1 → F5 (marco MVP executivo) é de poucos meses.
- Necessidade de evitar retrabalho se um contexto (ex.: pagamentos) precisar extrair no futuro.

## Alternativas consideradas

### Alt 1: Microservices por bounded context desde o MVP

- Prós: escalabilidade independente, isolamento de falha, stacks heterogêneas possíveis.
- Contras: operação distribuída (tracing, SAGA, eventual consistency) com time pequeno; reserva de assento exige locks globais via infra externa (Redis cluster + lock distribuído); 6× mais ambientes, pipelines, segredos.

### Alt 2: Monólito "big-ball-of-mud" sem fronteiras

- Prós: velocidade inicial ainda maior.
- Contras: acoplamento cresce sem freio; extrair pagamentos ou seating no futuro exige reescrita.

### Alt 3: Monólito modular Laravel com bounded contexts explícitos (escolhida)

- Prós: velocidade de monólito + fronteiras semânticas claras (`Actions/<Contexto>`, `Data/<Contexto>`, `Models/<Contexto>`, `Events/<Contexto>`). Arch tests Pest garantem acoplamento controlado. Transações `DB::transaction` naturais. Observabilidade nativa com Horizon + Pulse + Sentry. Estrada clara para extrair módulos futuros como serviços.
- Contras: escalabilidade por contexto só chega com extração (aceitável no MVP).

## Decisão

Adotar monólito modular Laravel 13 com fronteiras por bounded context (Adesão, Convites, RSVP, Seating, Extras, Pagamentos, Enquetes, Acesso, Cadastro, Comunicação). Cada contexto encapsula suas Actions, Data, Enums, Events, Exceptions, Models, Jobs, Listeners e Policies. Controllers de `Http\Api\V1`, `Http\Web\Admin` e Livewire consomem **apenas Actions**, nunca regras espalhadas.

Pest Architecture Tests (§1.3) fazem cumprir:

- `App\Actions` não pode usar `Illuminate\Http\*`.
- `App\Models` não pode importar `App\Actions`.
- Controllers só podem usar Actions, Data, Requests, Resources, Policies, Enums.

Comunicação entre contextos é por Events/Listeners ou chamada direta entre Actions quando a orquestração exige transação única (ex.: `ConfirmarPagamentoExtraAction` → `EmitirLoteConvitesAction`).

## Consequências positivas

- Transações cross-context ACID com `DB::transaction`.
- Deploy, observabilidade e ambientes unificados; SRE operável por time pequeno.
- Custo de extração futuro é baixo porque as fronteiras já estão codificadas em namespaces e validadas por arch tests.
- Mesmo pacote de regras serve admin (Blade/Livewire) e API v1 (PLANEJAMENTO §Apêndice D item 8).

## Consequências negativas

- Escalabilidade horizontal única até extração. Mitigação: Redis para locks/queues escala separadamente; Horizon permite supervisor por tipo de workload (§7.2).
- Um bug em contexto X pode, em teoria, derrubar o monólito inteiro. Mitigação: Pest cobre paths críticos; Horizon isola workers por fila; circuit breaker no gateway externo.

## Ligações

- §0, §1.1, §1.2, §1.3 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0001 (API-first), ADR-0011 (Horizon), ADR-0010 (Enums)
- SAD arc42 seções "Visão de blocos" e "Decisões arquiteturais"
