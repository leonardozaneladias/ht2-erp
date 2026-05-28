---
title: Documentação QA — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# Índice QA — Portal ArtFinal v2 (Backend API v1)

Este índice centraliza toda a documentação de Qualidade e Testes do backend API v1. É o ponto de entrada obrigatório antes de iniciar qualquer trabalho de QA, code review ou PR.

## Escopo

Todos os documentos aqui se aplicam ao backend Laravel 13 / PHP 8.4 que expõe a API v1 (`/api/v1/*`) e os webhooks em `/webhooks/*`. Frontend React, mobile React Native e admin Livewire consomem essa API — os critérios específicos de cada superfície são referenciados quando se integram à API.

## Documentos

| #   | Documento                                            | Papel                                                                                                                               |
| --- | ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| 1   | [`qa-strategy.md`](./qa-strategy.md)                 | Estratégia geral, pirâmide de testes, tipos, cobertura por contexto, ferramentas, gates por fase F1–F8, DoD, métricas de qualidade. |
| 2   | [`acceptance-criteria.md`](./acceptance-criteria.md) | Critérios de aceite em BDD (Gherkin) PT-BR por bounded context. ~140 cenários categorizados.                                        |
| 3   | [`test-plan.md`](./test-plan.md)                     | Organização de pastas, factories, seeders, mocks, execução paralela, exemplos Pest, comandos Makefile.                              |
| 4   | [`critical-scenarios.md`](./critical-scenarios.md)   | Cenários bloqueantes §10.7 (concorrência de assento, idempotência de webhook etc.) com snippets Pest e SLA.                         |
| 5   | [`nfr-tests.md`](./nfr-tests.md)                     | Requisitos não-funcionais: performance (k6), segurança (OWASP), LGPD, resiliência, acessibilidade.                                  |

## Relacionamento com outros documentos

- Planejamento técnico executável: [`../prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) — §10 (testes), §11 (segurança), §5 (concorrência), §14 (cronograma), Apêndice A (pré-F1).
- PRD de produto: [`../prd/PRD_v4.md`](../prd/PRD_v4.md) — §1.6 métricas.
- Regras de negócio: [`../prd/REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md) — 14 seções de regras + §15 (regras para testes).
- Arquitetura detalhada: [`../prd/ARQUITETURA_DETALHADA.md`](../prd/ARQUITETURA_DETALHADA.md).
- Performance: [`../prd/PERFORMANCE.md`](../prd/PERFORMANCE.md).
- Segurança: [`../prd/SEGURANCA.md`](../prd/SEGURANCA.md).

## Fluxo recomendado de leitura

1. `qa-strategy.md` — entenda a estratégia geral e a pirâmide.
2. `acceptance-criteria.md` — identifique os cenários do contexto que você vai trabalhar.
3. `test-plan.md` — escreva os testes seguindo o padrão.
4. `critical-scenarios.md` — garanta que os cenários bloqueantes têm cobertura antes do merge.
5. `nfr-tests.md` — quando for revisar, lançar fase ou rodar ciclo de hardening.

## Convenções

- Todo o conteúdo em PT-BR; termos técnicos PHP/Laravel permanecem em inglês.
- IDs de critério de aceite seguem o padrão `AC-<CONTEXTO>-<NNN>` (ex.: `AC-SEA-007`).
- Prioridades seguem MoSCoW: `must`, `should`, `could`.
- Referências a seções do planejamento usam a notação `§X.Y`.

## Governança

Este conjunto de documentos é **fonte de verdade** de QA. Qualquer divergência entre um teste real e um critério aqui descrito é bug de documentação ou de implementação — nunca ambos ficam errados em silêncio.

Mudança relevante em regra de negócio exige:

1. Atualizar `REGRAS_NEGOCIO.md`.
2. Atualizar critério(s) afetado(s) em `acceptance-criteria.md`.
3. Atualizar/criar teste(s) correspondente(s).
4. Bump de `version` no frontmatter do arquivo alterado.
