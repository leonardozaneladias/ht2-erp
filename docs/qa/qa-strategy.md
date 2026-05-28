---
title: QA Strategy — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# QA Strategy — Portal ArtFinal v2 (Backend API v1)

Documento-mãe da estratégia de qualidade do backend API v1. Define missão, modelo de pirâmide, tipos de teste, cobertura exigida por contexto, ferramental, ambientes, gates por fase F1–F8, políticas de regressão, DoD e métricas de qualidade.

---

## 1. Missão

Garantir que o backend API v1 do Portal ArtFinal evolua com **alta confiança de mudança** e **nenhum regresso silencioso** nos fluxos críticos de domínio — especialmente concorrência de assento, idempotência de pagamento, cálculo de cotas, emissão de convites derivados e elegibilidade de enquetes — ao longo de oito fases de construção (F1 a F8) que entregam incrementos em monólito modular Laravel.

## 2. Objetivos

1. **Prevenir regressão** em comportamento observável do domínio antes de cada merge em `main`.
2. **Documentar expectativa** de cada regra de negócio via teste automatizado executável, não apenas prosa.
3. **Proteger contratos externos**: API pública v1 e webhooks têm compatibilidade verificada automaticamente contra OpenAPI.
4. **Detectar corrida** em operações concorrentes (seating, webhook, idempotency-key) por testes de concorrência real.
5. **Honrar SLAs**: p95 de listagens críticas ≤ 500 ms e p95 de reserva de assento ≤ 700 ms (PRD v4 §1.6).
6. **Reduzir MTTR**: quando um defeito entra em produção, o ciclo detectar → reproduzir → corrigir → publicar fica ≤ 24h para severidade alta.
7. **Habilitar refactor contínuo**: cobertura e arch tests permitem grandes refatorações sem medo.

## 3. Princípios

- **Teste antes de escrever produção** para regras de negócio obrigatórias (TDD quando houver ambiguidade semântica).
- **Testes rápidos em paralelo**: suíte inteira ≤ 3 minutos em CI.
- **Um bug = um teste de regressão** que reproduz o problema antes da correção ser aceita.
- **Falhar alto** quando o comportamento muda sem intenção: arch tests + contract tests.
- **Cobertura é consequência, não alvo**: perseguimos proteção, não percentual bonito.
- **Factories > fixtures**: todo dado de teste vem de factory tipada com states nomeados.
- **Determinismo > conveniência**: nenhum teste depende de relógio externo, rede, ordem aleatória ou variável de ambiente não controlada.

## 4. Pirâmide de testes

A pirâmide adotada segue a distribuição **Unit 60% / Integration (Feature) 30% / E2E (Browser) 10%** e adiciona **quatro categorias transversais** (Arch, Contract, Concurrency, Performance).

### 4.1 Distribuição numérica alvo

| Camada                 | % da suíte  | Estimativa F6   | Justificativa                                                                                  |
| ---------------------- | ----------- | --------------- | ---------------------------------------------------------------------------------------------- |
| Unit (Pest Unit)       | 60%         | ~600 testes     | Testa pequenas unidades puras (actions, services, DTOs, enums, policies, helpers). Rápido.     |
| Feature (Pest Feature) | 30%         | ~300 testes     | Testa fluxos HTTP da API v1 + webhooks com banco real (RefreshDatabase).                       |
| Browser (Pest v4)      | 10%         | ~100 testes     | Admin Livewire end-to-end em navegador real. Usa Playwright via Pest v4.                       |
| Arch                   | transversal | ~30 testes      | Invariantes arquiteturais: actions não tocam HTTP, strict_types em 100%, namespaces coerentes. |
| Contract               | transversal | ~40 testes      | Valida que implementação bate com spec OpenAPI gerada via Scramble (spectator).                |
| Concurrency            | transversal | ~15 testes      | pcntl_fork + unique parcial: prova que race não gera duplicata.                                |
| Performance            | out-of-CI   | ~20 k6 cenários | Carga e p95 contra staging, não bloqueia PR; bloqueia promoção a prod.                         |

### 4.2 Justificativa da forma

- **Base ampla de Unit** porque o backend é dominado por actions puras (`execute()`), DTOs e enums — testáveis sem boot completo do framework.
- **Meia-altura de Feature** porque o contrato público (API v1) é o produto principal; precisamos validar rotas, middlewares, policies e envelope de erro §2.11 ponta-a-ponta.
- **Topo estreito de Browser** porque o admin Livewire é interno (menor superfície de bug de regressão UX) e custa caro rodar. Formando web e mobile não entram nesta suíte — são testados separadamente nas superfícies React/RN.
- **Arch é barato, cobre muito**: um arch test protege 100% dos arquivos contra violações estruturais por um custo ~linear no número de classes.
- **Contract é barato e faz o OpenAPI ser mais do que decoração**: toda resposta da API v1 em teste é validada contra a spec.
- **Concurrency não cabe em unit nem feature**: exige fork de processos ou `--parallel` real.

## 5. Tipos de teste — catálogo completo

### 5.1 Unit (Pest Unit)

- **O que testa:** actions (`execute()`), services, DTOs, enums, value objects, helpers, policies puras (sem request), resolvers de regra (ex.: `CotaCalculator`), strategies, invariantes de model (casts, scopes, mutators).
- **Onde:** `tests/Unit/<Contexto>/`.
- **Banco:** in-memory SQLite `:memory:` via `RefreshDatabase`, ou sem banco quando possível (preferido).
- **Ferramental:** Pest 4, factories, `Mockery`/`Http::fake()`/`Queue::fake()` para bordas.
- **Regras:**
    - Cada action tem **um** happy path + todos os caminhos de falha documentados em `acceptance-criteria.md`.
    - DTO tem teste de serialização (`toArray()`) e desserialização de input (cada caso borda).
    - Enum tem teste de `label()`, `color()` e `from()`/`tryFrom()` exaustivo.
    - Policy tem teste para cada combinação ator × recurso definida em produção.

### 5.2 Feature (Pest Feature)

- **O que testa:** endpoints HTTP da API v1 + webhooks `/webhooks/*`. Cada rota registrada em `routes/api/v1.php` e `routes/webhook.php` tem pelo menos um feature test positivo e um negativo.
- **Onde:** `tests/Feature/Api/V1/<Contexto>/` e `tests/Feature/Webhook/`.
- **Banco:** PostgreSQL real em CI (`RefreshDatabase` + transactional), pois dependemos de recursos específicos PG (JSONB, `EXCLUDE`, unique parcial).
- **Matriz mínima por endpoint:**
    1. 2xx happy path com payload válido e ator autorizado.
    2. 401 sem token / sem sessão.
    3. 403 com ator sem permissão.
    4. 404 com recurso inexistente.
    5. 409 transição de estado inválida.
    6. 422 payload inválido com `details.fields` (conforme envelope §2.11).
    7. 429 quando rate limit estoura.
- **Assertions obrigatórias:** status, estrutura do JSON (via `assertJsonStructure`), envelope §2.11 nos erros, efeitos colaterais no banco (`assertDatabaseHas`), side-effects em filas (`Queue::assertPushed`) ou mail (`Mail::assertQueued`).

### 5.3 Arch (Pest\Arch)

Exigidos pelo planejamento §1.3. Objetivo: travar a arquitetura antes que derive.

```php
arch('actions não tocam HTTP')
    ->expect('App\Actions')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Support\Facades\Route',
        'Illuminate\Support\Facades\Redirect',
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);

arch('models não tocam actions')
    ->expect('App\Models')
    ->not->toUse('App\Actions');

arch('controllers finos — proibido DB facade')
    ->expect('App\Http\Api\V1\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB'])
    ->ignoring('App\Http\Api\V1\Controllers\Debug');

arch('actions com execute() público')
    ->expect('App\Actions')
    ->toHaveMethod('execute');

arch('policies nomeadas com suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

arch('strict_types em 100%')
    ->expect(['App', 'tests'])
    ->toUseStrictTypes();

arch('enums no namespace correto')
    ->expect('App\Enums')
    ->toBeEnums();

arch('DTOs são readonly')
    ->expect('App\Data')
    ->toBeReadonly();
```

### 5.4 Browser (Pest v4)

- **O que testa:** fluxos críticos do admin Livewire (login MFA, CRUD de evento, emissão de convite em lote, desenho de mapa de mesas).
- **Onde:** `tests/Browser/Admin/`.
- **Ferramental:** Pest 4 com `visit()`, `click()`, `fill()`, `assertSee()`.
- **Frequência:** roda em CI em jobs dedicados (matrix) ou em `nightly`, não em todo PR (custo).

### 5.5 Contract (OpenAPI ↔ implementação)

- **Ferramenta:** `dedoc/scramble` gera a spec em build; `hkulekci/spectator` (ou equivalente) valida cada response em feature test contra a spec.
- **Workflow:**
    1. Build gera `docs/api.json` a partir dos atributos PHP 8 + annotations.
    2. Feature tests carregam a spec e validam request/response.
    3. CI falha se endpoint novo não estiver documentado ou se schema drifou.
- **Pasta:** `tests/Feature/Contract/`.

### 5.6 Concurrency

- **O que testa:** cenários onde ordem/simultaneidade determinam o resultado.
- **Técnicas:**
    1. `pcntl_fork` para disparar N processos paralelos reais contra o mesmo recurso.
    2. Pest `--parallel` para dividir a suíte em workers independentes.
    3. `DB::transaction` + `lockForUpdate` exercitados sob concorrência simulada.
- **Cenários obrigatórios:** `critical-scenarios.md` itens 1, 2, 3, 8.
- **Pasta:** `tests/Concurrency/`.
- **Rodar:** `make test-concurrent`.

### 5.7 Performance

- **Ferramenta:** k6 (JavaScript-based load testing).
- **Ambiente:** staging — nunca CI.
- **Thresholds:**
    - `http_req_duration{endpoint:lista_eventos}`: p95 < 500 ms.
    - `http_req_duration{endpoint:reservar_assento}`: p95 < 700 ms.
    - `http_req_failed`: < 1%.
- **Cenários-chave:** listagem de mapa (1.000 req/min), reserva de assento (100 req/min), emissão de lote de 500 convites (≤ 60s).
- Detalhes: `nfr-tests.md`.

### 5.8 Security

- **OWASP Top 10** mapeado para testes automatizados:
    - A01 Broken Access Control: cobertura via policies em feature tests.
    - A02 Cryptographic Failures: testes de `sha256` em token de convite, HMAC em webhook, nunca logar `token`.
    - A03 Injection: raw queries explicitamente proibidos via arch test; testes de SQL injection em filtros (parâmetros hostis).
    - A04 Insecure Design: threat model revisado por fase em `nfr-tests.md`.
    - A05 Security Misconfiguration: teste de headers HTTP (HSTS, CSP, X-Frame-Options).
    - A07 Identification and Authentication Failures: brute-force em login com rate limit; replay em JWT/Bearer.
    - A08 Software and Data Integrity Failures: HMAC obrigatório em webhook; assinatura inválida → 401.
    - A09 Security Logging and Monitoring Failures: teste de mascaramento de CPF/token em logs.

### 5.9 Smoke

- **O que testa:** após deploy em staging ou prod, bateria mínima de ~50 requests GET em endpoints principais respondendo 2xx/3xx; 0 exceções 5xx.
- **Ferramenta:** script k6 + Pest v4 browser para admin.
- **Gate de promoção:** smoke verde é pré-requisito para aprovação manual em prod.

## 6. Cobertura por bounded context

Replicando §10.1 do planejamento, estendendo com números-alvo.

| Contexto           | Feature | Unit    | Arch    | Concorrência       | Cobertura mín. linhas |
| ------------------ | ------- | ------- | ------- | ------------------ | --------------------- |
| Identidade/Acesso  | sim     | —       | sim     | —                  | 85%                   |
| Cadastro           | sim     | sim     | sim     | —                  | 80%                   |
| Comercial/Adesão   | sim     | sim     | sim     | —                  | 85% (cota 100%)       |
| Convites           | sim     | sim     | sim     | —                  | 85%                   |
| RSVP               | sim     | sim     | sim     | —                  | 85%                   |
| **Seating**        | **sim** | **sim** | **sim** | **sim (crítico)**  | **95%**               |
| Extras             | sim     | sim     | sim     | —                  | 85%                   |
| Pagamentos/Webhook | sim     | sim     | sim     | sim (idempotência) | 95%                   |
| Enquetes           | sim     | sim     | sim     | —                  | 85%                   |
| Comunicação        | sim     | sim     | sim     | —                  | 80%                   |

**Cobertura de classes por categoria:**

- Actions: **100%** (toda action tem ao menos 1 happy path + 1 falha).
- Controllers: **80%** (exceto debug).
- Jobs: **90%**.
- Policies: **100%** (toda combinação ator × recurso).
- DTOs/Enums: **100%** (enum exhaustive, DTO round-trip).
- Observers/Listeners: **85%**.

## 7. Ferramentas

| Ferramenta                    | Versão  | Uso                                          | Config                  |
| ----------------------------- | ------- | -------------------------------------------- | ----------------------- |
| Pest                          | ^4.0    | Unit, Feature, Arch, Browser                 | `pest.config.php`       |
| PHPUnit                       | ^12.0   | Runner subjacente do Pest                    | `phpunit.xml`           |
| Larastan (PHPStan)            | level 6 | Análise estática                             | `phpstan.neon`          |
| Laravel Pint                  | ^1.0    | PHP formatter (PSR-12 + Laravel)             | `pint.json`             |
| Prettier + plugin blade/twcss | ^3.0    | Formatação de Blade, JS, MD, JSON            | `.prettierrc`           |
| ESLint                        | ^10.0   | Linter JS                                    | `.eslintrc.json`        |
| Husky + lint-staged           | ^9.0    | Pre-commit: pint + prettier + phpstan mínimo | `.husky/pre-commit`     |
| Scramble                      | ^0.12   | OpenAPI autogen                              | `config/scramble.php`   |
| Spectator (contract)          | ^2.0    | Valida response contra OpenAPI               | env `OPENAPI_SPEC_PATH` |
| k6                            | ^0.48   | Load / performance                           | `nfr-tests/k6/*.js`     |
| Pail                          | ^1.0    | Debug de logs em dev local                   | `php artisan pail`      |
| Debugbar                      | ^3.0    | Debug em dev local (N+1 watcher)             | `.env APP_DEBUG=true`   |
| Pulse                         | ^1.0    | Métricas em prod                             | `config/pulse.php`      |
| Horizon                       | ^5.0    | Supervisão de filas                          | `config/horizon.php`    |

### 7.1 Qualidade agregada

Comando único `make quality` (documentado em `INFRA.md`):

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=2G
php artisan test --compact --parallel
npx prettier --check resources/
npx eslint resources/js/
```

Falha um → falha o commit.

## 8. Ambientes

| Ambiente | Finalidade                                          | Dados                                 | Observações                                           |
| -------- | --------------------------------------------------- | ------------------------------------- | ----------------------------------------------------- |
| local    | dev individual                                      | Docker + `DevelopmentSeeder`          | `make fresh` reseta; `make test` roda suíte.          |
| CI       | validação de PR                                     | PostgreSQL containerizado + factories | GitHub Actions; jobs pint, phpstan, pest, build.      |
| staging  | pré-produção, réplica de schema e volumes similares | Seed anonimizado + subset real        | Webhook Itaú sandbox; onde roda k6 e smoke test.      |
| prod     | produção                                            | Real                                  | Smoke test pós-deploy; monitorado por Pulse + Sentry. |

### 8.1 Matriz de gates por ambiente

| Gate                       | local    | CI                  | staging    | prod       |
| -------------------------- | -------- | ------------------- | ---------- | ---------- |
| Pint                       | opcional | sim                 | —          | —          |
| PHPStan level 6            | opcional | sim                 | —          | —          |
| Pest Unit + Feature + Arch | opcional | sim                 | —          | —          |
| Pest Browser (admin)       | —        | nightly             | —          | —          |
| Contract (OpenAPI)         | —        | sim                 | —          | —          |
| Concurrency                | opcional | sim (label `@slow`) | —          | —          |
| k6 load                    | —        | —                   | manual     | —          |
| Smoke test                 | —        | —                   | pós-deploy | pós-deploy |
| OWASP scan (ZAP)           | —        | nightly             | semanal    | mensal     |

## 9. Dados de teste

Política: **factories tipadas sempre, fixtures raras, seed apenas em dev/staging**.

- **Factories** por model em `database/factories/<Contexto>/XxxFactory.php`.
- **States nomeados**: `ativo`, `expirado`, `hold`, `confirmada`, `cancelada`, `pendente_pagamento`, `pago`.
- **TestSeeder** (determinístico) para testes que precisam de setup complexo (ex.: mapa 10×8 + 20 formandos).
- **DevelopmentSeeder** (não determinístico, aleatório com faker PT-BR) para dev e staging.
- **Anonimização em staging**: mesmo schema de produção, mas dados pessoais substituídos por `faker->name()`, CPFs gerados válidos, emails `@example.test`.

## 10. Fluxo CI/CD

```
          ┌─── feature branch ───┐
          │                      │
   commit │                      │ push
          ▼                      ▼
  husky pre-commit         GitHub Actions:
  (pint + prettier)        ┌────────────────────┐
                           │ 1. pint --test     │
                           │ 2. phpstan level 6 │
                           │ 3. pest parallel   │
                           │ 4. arch tests      │
                           │ 5. contract tests  │
                           │ 6. build assets    │
                           └────────────────────┘
                                   │
                                   ▼
                          PR status check verde
                                   │
                                   ▼
                             code review
                                   │
                                   ▼
                            merge em main
                                   │
                                   ▼
                          deploy automatizado
                                 staging
                                   │
                                   ▼
                          smoke test staging
                                   │
                                   ▼
                    aprovação manual do QA Lead
                                   │
                                   ▼
                            deploy prod
                                   │
                                   ▼
                            smoke test prod
```

### 10.1 Regras

- PR com arch ou contract vermelho é bloqueado.
- PR sem teste para código novo em action/policy/job é bloqueado por convenção de review (não automatizado em F1; automatizado em F7 via `infection` ou cobertura delta).
- Hotfix pode pular staging **mas não pula smoke**.
- Rollback é automático se smoke falhar em prod.

## 11. Gates por fase F1–F8

Critérios espelhando §14 do planejamento, detalhados pelo ângulo QA.

### 11.1 F1 — Fundação (34 SP)

| Gate                                                                              | Obrigatório antes de fechar F1 |
| --------------------------------------------------------------------------------- | ------------------------------ |
| `php artisan test --compact` passa 100%.                                          | sim                            |
| `phpstan analyse --level=6` zero erros.                                           | sim                            |
| `pint --test` zero drifts.                                                        | sim                            |
| Arch tests §1.3 todos verdes (strict_types, controllers finos, actions sem HTTP). | sim                            |
| `GET /api/v1/me` retorna 401 sem token e 200 com Sanctum.                         | sim                            |
| `POST /api/v1/convite/{token}/rsvp` resolve token via middleware custom.          | sim                            |
| Handler global testado: 401, 403, 422 com envelope §2.11.                         | sim                            |
| Spec OpenAPI gerada via Scramble (`GET /docs/api.json`) íntegra.                  | sim                            |
| Apêndice A (checklist pré-F1) 100% checado.                                       | sim                            |
| CI configurado com jobs pint, phpstan, pest, build.                               | sim                            |

### 11.2 F2 — Admin estrutural (40 SP)

| Gate                                                                                         |
| -------------------------------------------------------------------------------------------- |
| CRUD completo de `Organizacao`, `Instituicao`, `Curso`, `Turma`, `Evento` com feature tests. |
| ACL Spatie com teste por role (comissão vs admin) em todas as rotas.                         |
| Browser tests do login admin + dashboard KPI.                                                |
| Cobertura de Contexto Cadastro ≥ 80%.                                                        |

### 11.3 F3 — Cliente web React (34 SP)

| Gate                                                                 |
| -------------------------------------------------------------------- |
| Endpoints consumidos pela SPA têm feature tests com cookies Sanctum. |
| Fluxo login SPA (csrf-cookie + login) coberto ponta-a-ponta.         |
| Contract tests validam toda response consumida pela SPA.             |

### 11.4 F4 — Convites e RSVP (28 SP)

| Gate                                                                                                |
| --------------------------------------------------------------------------------------------------- |
| Actions de convite (`EmitirConvite`, `EmitirLoteConvites`, `Cancelar`, `Transferir`) 100% cobertas. |
| Token de convite: teste de geração com `bin2hex(random_bytes(32))`, hash persistido.                |
| Middleware `ResolveConviteToken`: 404 para inexistente, revogado, expirado.                         |
| RSVP público funcional; 10+ cenários em `acceptance-criteria.md`.                                   |
| Emissão em lote de 500 convites ≤ 60s (teste de performance).                                       |
| Cobertura do contexto ≥ 80%.                                                                        |

### 11.5 F5 — Seating (34 SP, fase crítica)

| Gate                                                                          |
| ----------------------------------------------------------------------------- |
| **1.000 tentativas simultâneas no mesmo assento → 1 vence** (teste de carga). |
| P95 de reserva ≤ 700 ms em k6.                                                |
| Unique parcial ativo em `reservas_assentos`.                                  |
| Job `ExpirarHoldsJob` testado (hold expira ≤ 5 min).                          |
| Troca de assento sem deadlock (ordem fixa ASC, teste de concorrência).        |
| Cobertura do contexto ≥ 95%.                                                  |

### 11.6 F6 — Extras + pagamentos + enquetes (34 SP)

| Gate                                                                             |
| -------------------------------------------------------------------------------- |
| **Webhook reprocessado 10× não dobra efeito** (idempotência).                    |
| HMAC inválida → 401 sem side-effect.                                             |
| Convite extra pós-pagamento confirmado emitido em ≤ 30s.                         |
| Voto único por ator / elegibilidade baseada em regra JSONB testada com fixtures. |
| Cobertura pagamentos ≥ 95%.                                                      |

### 11.7 F7 — Hardening e observabilidade (21 SP)

| Gate                                                                 |
| -------------------------------------------------------------------- |
| Audit log ≥ 95% dos eventos críticos (lista §14 REGRAS_NEGOCIO).     |
| Job `AnonimizarDadosPosEventoJob` executa em schedule e passa teste. |
| Endpoint `DELETE /api/v1/me` implementado e testado (LGPD).          |
| Pulse dashboards custom no ar; alertas configurados §12.3.           |
| OWASP scan semanal sem criticidade alta.                             |

### 11.8 F8 — Mobile MVP (34 SP)

| Gate                                                                               |
| ---------------------------------------------------------------------------------- |
| Contract tests mobile-specific (Authorization Bearer flow).                        |
| Push notifications testadas com Expo sandbox.                                      |
| Seating simplificado (apenas confirmar assento reservado) testado em concorrência. |

## 12. Política de regressão

Todo bug em produção (ou capturado em staging) exige:

1. **Reprodução** via teste automatizado que falha no código atual.
2. **Correção** que torna o teste verde.
3. **Merge junto**: teste + correção no mesmo PR. Nunca teste sem correção, nunca correção sem teste.
4. **Tag no changelog**: `fix(contexto): descrição breve — PAF-XXX`.
5. **Se recorrente** (mesmo bug voltou): análise de root cause, arch test ou invariante de domínio para travar a classe de problema inteira.

**SLA de resposta**:

| Severidade | Definição                                           | MTTR alvo |
| ---------- | --------------------------------------------------- | --------- |
| P0         | Outage total; seating, pagamento ou login quebrados | ≤ 2h      |
| P1         | Feature crítica degradada; workaround existe        | ≤ 24h     |
| P2         | Bug funcional sem workaround; baixo impacto         | ≤ 7d      |
| P3         | Issue cosmético ou UX                               | backlog   |

## 13. Definition of Done (DoD) por PR

Um PR **só pode** receber merge em `main` se **todos** os itens abaixo estiverem verificáveis.

1. [ ] Código formatado com Pint (`./vendor/bin/pint --test` verde).
2. [ ] Análise estática limpa (`phpstan analyse --level=6` zero erros).
3. [ ] Suíte Pest verde (`php artisan test --compact --parallel`).
4. [ ] Arch tests verdes — invariantes não foram quebradas.
5. [ ] Contract tests verdes — spec OpenAPI reflete a implementação.
6. [ ] Nova action/job/policy tem teste dedicado (unit + feature quando HTTP).
7. [ ] Regra de negócio nova ou modificada tem critério em `acceptance-criteria.md` com ID atribuído.
8. [ ] Qualquer bug corrigido tem teste de regressão incluído no mesmo PR.
9. [ ] Factories e states cobrem os casos novos; nenhuma fixture inline duplicada.
10. [ ] `docs/qa/*` atualizado se a mudança altera comportamento público ou expectativa.
11. [ ] Changelog incrementado se a mudança afeta API pública (`docs/api/CHANGELOG.md`).
12. [ ] Issue do Plane referenciada no commit/PR (`PAF-XXX`).
13. [ ] Nenhum `dd()`, `dump()`, `var_dump()`, `TODO` órfão, `console.log` ou credencial em código.

## 14. Métricas de qualidade

Monitoradas em dashboard Pulse custom + export CSV semanal.

### 14.1 Métricas primárias

| Métrica                              | Alvo               | Como medir                         |
| ------------------------------------ | ------------------ | ---------------------------------- |
| Cobertura de linhas global           | ≥ 80%              | `pest --coverage`                  |
| Cobertura de actions                 | 100%               | `pest --coverage --filter=Actions` |
| Cobertura de seating                 | ≥ 95%              | `pest --coverage --filter=Seating` |
| % testes flaky (falham intermitente) | < 1%               | CI histórico de reruns             |
| Tempo total da suíte (CI)            | ≤ 3 min (paralelo) | GitHub Actions duration            |
| MTTR P0                              | ≤ 2h               | Sentry + Plane timestamps          |
| MTTR P1                              | ≤ 24h              | idem                               |
| Escape rate (bugs/1000 LoC/mês)      | < 0,5              | bugs P0+P1 no mês / kLoC PR        |
| Test coverage delta por PR           | ≥ 0                | cobertura depois ≥ antes           |

### 14.2 Métricas secundárias

- N+1 detectado por PR (via Telescope/Debugbar em dev) → 0 merged.
- Queries > 100ms por request em staging → alerta.
- Drift entre OpenAPI e implementação → sempre 0.
- Dependências com vulnerabilidade conhecida (composer audit + npm audit) → 0 `high`/`critical`.

## 15. Papéis e responsabilidades

| Papel     | Responsabilidade                                                                |
| --------- | ------------------------------------------------------------------------------- |
| Dev       | Escreve teste com código; garante DoD; corrige bug com teste de regressão.      |
| QA Lead   | Mantém `docs/qa/*`; aprova gates de fase; define novos critérios; triagem bug.  |
| Tech Lead | Aprova exceções a arch tests; decide trade-offs de cobertura.                   |
| SRE/Infra | k6 em staging; configura alertas Pulse; owner do smoke test.                    |
| Produto   | Aprova critérios `must` em `acceptance-criteria.md`; valida cenário em staging. |

## 16. Exceções e desvios documentados

Qualquer desvio desta estratégia exige:

1. ADR em `.adr/` explicando o desvio.
2. Arch test ou teste específico que trave o escopo do desvio (para não virar padrão).
3. Prazo para retorno à norma (ex.: "desvio válido até fim de F5").

## 17. Referências

- Planejamento: `.docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §10, §11, §5, §14, Apêndice A.
- PRD: `.docs/prd/PRD_v4.md` §1.6.
- Regras de negócio: `.docs/prd/REGRAS_NEGOCIO.md` §15.
- Laravel Boost guidelines: `CLAUDE.md` §10.
