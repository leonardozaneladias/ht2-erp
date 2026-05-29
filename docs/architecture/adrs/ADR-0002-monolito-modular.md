---
title: 'ADR-0002: Monólito modular Laravel em vez de microservices'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0002: Monólito modular Laravel em vez de microservices

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Arquitetura | **Tags:** arquitetura, topologia, modularidade

## Contexto e problema

A aplicação tem domínio coeso, com fortes invariantes transacionais entre contextos. O time é pequeno e o SLA alvo é alto.

A decisão em aberto é: começar com microservices (um serviço por bounded context) ou monólito modular Laravel com fronteiras internas explícitas?

## Drivers da decisão

- Custo operacional de infra e observabilidade distribuída vs tamanho do time.
- Transações cross-context (ex.: confirmar um pagamento → emitir os registros derivados) precisam ser atômicas.
- Time-to-market curto.
- Necessidade de evitar retrabalho se um contexto precisar ser extraído no futuro.

## Alternativas consideradas

### Alt 1: Microservices por bounded context desde o início

- Prós: escalabilidade independente, isolamento de falha, stacks heterogêneas possíveis.
- Contras: operação distribuída (tracing, SAGA, eventual consistency) com time pequeno; invariantes transacionais exigem coordenação via infra externa; muito mais ambientes, pipelines e segredos.

### Alt 2: Monólito "big-ball-of-mud" sem fronteiras

- Prós: velocidade inicial ainda maior.
- Contras: acoplamento cresce sem freio; extrair qualquer contexto no futuro exige reescrita.

### Alt 3: Monólito modular Laravel com bounded contexts explícitos (escolhida)

- Prós: velocidade de monólito + fronteiras semânticas claras (`Actions/<Contexto>`, `Data/<Contexto>`, `Models/<Contexto>`, `Events/<Contexto>`). Arch tests Pest garantem acoplamento controlado. Transações `DB::transaction` naturais. Observabilidade nativa com Horizon + Pulse. Estrada clara para extrair módulos futuros como serviços.
- Contras: escalabilidade por contexto só chega com extração (aceitável).

## Decisão

Adotar monólito modular Laravel com fronteiras por bounded context. Cada contexto encapsula suas Actions, Data, Enums, Events, Exceptions, Models, Jobs, Listeners e Policies. Controllers e componentes Livewire consomem **apenas Actions**, nunca regras espalhadas.

Pest Architecture Tests fazem cumprir:

- `App\Actions` não pode usar `Illuminate\Http\*`.
- `App\Models` não pode importar `App\Actions`.
- Controllers só podem usar Actions, Data, Requests, Policies, Enums.

Comunicação entre contextos é por Events/Listeners ou chamada direta entre Actions quando a orquestração exige transação única (ex.: uma Action de confirmação dispara a Action que cria os registros derivados).

## Consequências positivas

- Transações cross-context ACID com `DB::transaction`.
- Deploy, observabilidade e ambientes unificados; operável por time pequeno.
- Custo de extração futuro é baixo porque as fronteiras já estão codificadas em namespaces e validadas por arch tests.

## Consequências negativas

- Escalabilidade horizontal única até extração. Mitigação: Redis para locks/queues escala separadamente; Horizon permite supervisor por tipo de workload.
- Um bug em contexto X pode, em teoria, derrubar o monólito inteiro. Mitigação: Pest cobre paths críticos; Horizon isola workers por fila.

## Ligações

- ADR-0011 (Horizon), ADR-0010 (Enums)
