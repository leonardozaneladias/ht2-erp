---
title: 'ADR-0001: API-first com prefixo api/v1 desde o dia 1'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0001: API-first com prefixo `api/v1` desde o dia 1

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Arquitetura | **Tags:** api, versionamento, http, contrato

## Contexto e problema

O produto Portal ArtFinal precisa servir, desde o MVP, três consumidores distintos: admin interno (Livewire/Blade), cliente web React (SPA) e app mobile em React Native. Esses consumidores têm ciclos de release, stacks e telas independentes, porém consomem o mesmo domínio de negócio.

Sem um contrato HTTP versionado e estável, cada consumidor acaba encontrando uma "forma paralela" de chamar o backend (ex.: SPA via controllers Livewire não reutilizáveis no mobile), multiplicando a superfície de manutenção e violando o princípio "core independente da camada HTTP" (PLANEJAMENTO_BACKEND_APIV1 §0 item 2 e §1.2).

## Drivers da decisão

- Necessidade de um contrato único servindo web + mobile (PRD §7) já em F3.
- Obrigação de poder evoluir a API sem quebrar clientes já publicados.
- Clareza para geração automatizada de clients (orval, openapi-typescript) e documentação (§2.12).
- Ausência de vendor-locking em framework-specific auth para clients externos.

## Alternativas consideradas

### Alt 1: Sem prefixo de versão (`/api/*`)

- Prós: simplicidade inicial, menos digitação em URLs.
- Contras: qualquer breaking change obriga coordenação total entre backend e todos os clientes; mobile publicado em stores não pode ser atualizado à força; viola RFC 8594 de sunset.

### Alt 2: Versão via header (`Accept: application/vnd.artfinal.v1+json`)

- Prós: URLs mais limpas; permite negociação por conteúdo.
- Contras: cacheabilidade e debugging mais difíceis; ferramentas (curl, Postman, browser dev tools) mostram a mesma URL para versões distintas; comunidade Laravel/Scramble tem suporte pobre.

### Alt 3: Prefixo explícito `api/v1` (escolhida)

- Prós: contrato visível, cacheável, trivial de logar, suportado nativamente por Scramble e por `Route::prefix(...)`; alinhado com Stripe, GitHub, Atlassian.
- Contras: URLs mais longas; nova versão exige duplicação controlada da camada HTTP.

## Decisão

Todo endpoint consumível externamente fica sob `api/v1` desde o commit inicial de F1, registrado em `bootstrap/app.php` via `Route::prefix('api/v1')->middleware('api')->name('api.v1.')->group(...)`. Actions e DTOs são a fonte única de verdade; Controllers/Resources são por versão (`App\Http\Api\V1\*`). Breaking changes exigem nova pasta `V2` reaproveitando as mesmas Actions.

Política de deprecação segue RFC 8594: endpoints deprecados respondem `Deprecation: true`, `Sunset: <date>`, `Link: <successor>; rel="successor-version"` com notice mínimo de 90 dias. Após `Sunset`, resposta é `410 Gone`. Changelog fica em `docs/api/CHANGELOG.md`.

## Consequências positivas

- Clientes web e mobile convivem com versões diferentes sem coordenação forçada.
- Novos campos em respostas são adicionados de forma não-breaking via `$this->when(...)` em Resources, sem bump de versão.
- Scramble gera spec OpenAPI automático a partir das rotas versionadas; clients são gerados por orval/openapi-typescript.
- Suíte de testes de contrato (api-surface tests) fica fácil de segmentar por versão.

## Consequências negativas

- Duplicação controlada de Controllers/Requests/Resources quando surgir `v2`. Mitigação: Actions permanecem compartilhadas.
- URLs ligeiramente mais longas para admin e portal interno também (uniformidade intencional).

## Ligações

- §0 "Princípios não negociáveis" itens 2 e 3 do PLANEJAMENTO_BACKEND_APIV1.md
- §2.1, §2.2, §2.3 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0002 (monólito modular), ADR-0007 (Scramble), ADR-0004 (ULID público)
- SAD arc42 seções "Contexto e escopo" e "Visão de blocos"
