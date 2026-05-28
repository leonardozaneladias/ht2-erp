---
title: 'ADR-0007: OpenAPI gerado por Scramble (zero-anotação)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0007: OpenAPI gerado por Scramble (zero-anotação)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel | **Tags:** documentacao, openapi, api-tooling

## Contexto e problema

A API v1 serve clientes heterogêneos (SPA React, mobile RN, integrações admin). Sem spec OpenAPI mantida, cada cliente reinventa types e reescreve mocks; contratos divergem no tempo. A questão é: como manter spec OpenAPI 3.x automatizada, sem sobrecarga de anotações PHPDoc e sem violar o princípio "core independente da camada HTTP"?

## Drivers da decisão

- Geração automatizada de clients (orval para React/RN, openapi-typescript).
- Evitar anotações manuais em controllers — viola §1.2 (Actions não podem conhecer HTTP) e gera drift com o real comportamento.
- Preservar FormRequest + Resource como fonte única de verdade do shape da API.
- Compatibilidade com Laravel 13 e PHP 8.4.

## Alternativas consideradas

### Alt 1: `darkaonline/l5-swagger` com anotações `@OA\*`

- Prós: maduro, comunidade grande.
- Contras: exige anotar **cada controller** com `@OA\Post`, `@OA\Response`, `@OA\Schema`; manutenção cresce proporcional a endpoints; duplica informação que já está no FormRequest e Resource; PRs "trocam emoji na anotação" viram comum; spec fica desatualizado silenciosamente.

### Alt 2: Escrever spec OpenAPI à mão em `openapi.yaml`

- Prós: controle total.
- Contras: drift garantido em 2 semanas; ninguém atualiza; spec vira fantasia.

### Alt 3: `dedoc/scramble` (escolhida)

- Prós: lê FormRequests (`rules()` → schema de request), Resources (`toArray()` → schema de response), PHP type hints, Route attributes; gera OpenAPI 3.x; zero anotação em controllers; `GET /docs/api` serve UI Stoplight e `GET /docs/api.json` serve spec bruto; atualização automática a cada deploy.
- Contras: limitações em casos muito dinâmicos (ex.: `$this->when()` pode precisar hint); menor comunidade que l5-swagger; depende de a FormRequest/Resource estar bem tipada (o que já é exigência ADR e arch test).

## Decisão

Adotar `dedoc/scramble` como gerador único de OpenAPI. `config/scramble.php` configura:

- `api_path: 'api/v1'`
- `middleware: ['web', 'auth:admin']` em produção (docs protegido por gate `docs.api.view`)
- UI em `GET /docs/api`, spec em `GET /docs/api.json`

Clients (orval, openapi-typescript) consomem o JSON em CI. Apêndice A item 14 exige validação do endpoint `/docs/api.json` como critério de aceite da F1.

Alternativa l5-swagger fica formalmente rejeitada.

## Consequências positivas

- Spec sempre em sincronia com código (FormRequest + Resource são a verdade).
- Zero overhead de PR para documentar endpoint novo — basta seguir o padrão.
- Clients TS gerados reduzem drift entre backend e SPA/RN.
- Política de deprecação (ADR-0001) aparece no header `Deprecation`/`Sunset` automaticamente.

## Consequências negativas

- Casos dinâmicos (ex.: `$this->when()` condicional por role) precisam cuidado nos types — mitigado por convenção `mixed`/nullable explícito.
- Scramble evolui junto com Laravel; possível quebra em upgrades. Mitigação: pin de versão + CI verifica `/docs/api.json`.

## Ligações

- §2.12 do PLANEJAMENTO_BACKEND_APIV1.md (decisão + alternativa rejeitada)
- §2.4, §2.5, §2.14 (FormRequest + Resource + QueryBuilder)
- Apêndice A item 14
- ADR-0001 (API-first), ADR-0008 (verbos state-machine)
- SAD arc42 seção "Conceitos de corte transversal — Documentação"
