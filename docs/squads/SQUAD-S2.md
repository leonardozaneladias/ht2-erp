# Squad — S2: Portal Adesão Pública (SPEC-010)

> **Fase:** S2 (Portal-First — sucessor de F1)
> **Objetivo:** completar o fluxo público de adesão (`/adesao/{codigo}`) ponta a ponta — backend + SPA React
> **Story Points:** 47 SP (3 sprints)
> **Dependências:** F1 fechada ✅ · SPEC-F-009 (gateway fake) ✅ · SPEC-F-010 (auth) ✅
> **Status:** 🟡 Em planejamento
> **Atualizado em:** 2026-04-25
> **Justificativa:** Portal-First (CLAUDE.md §15) — desbloqueia MVP de receita antes do Admin estrutural. Substitui localmente F2=Admin do ROADMAP.

---

## Sprint plan — 3 sprints

| Sprint | Foco                                           | SP  | Entrega chave                      |
| ------ | ---------------------------------------------- | --- | ---------------------------------- |
| S2.1   | `POST /publico/simular` + cálculo de parcelas  | 13  | Plano de pagamento sob draft_token |
| S2.2   | `POST /publico/commit` + responsáveis + termos | 13  | Adesão criada + auto-login + email |
| S2.3   | SPA React 19 — Wizard público (5 etapas)       | 21  | Funil ponta a ponta no navegador   |

---

## Sprint S2.1 — Simular plano de pagamento

> Branch sugerida: `feature/spec010-simular-publico`

| Story                                                                     | Status | Skill primária       | Skill secundária         |
| ------------------------------------------------------------------------- | ------ | -------------------- | ------------------------ |
| SPEC-F-006 reescrita: regras de parcelamento (PIX/Boleto/Cartão)          | ⬜     | `php-best-practices` | `laravel-dtos`           |
| `SimularParcelasAction` (DTO IN/OUT, sem HTTP)                            | ⬜     | `laravel-actions`    | `laravel-dtos`           |
| Aplicação de descontos/condições do contrato (SPEC-F-005)                 | ⬜     | `php-best-practices` | `laravel-services`       |
| `SimulacaoData` DTO + `ParcelaPreviewData[]`                              | ⬜     | `laravel-dtos`       | `laravel-value-objects`  |
| `SimularAdesaoPublicaController` (route name `publico.adesao.simular`)    | ⬜     | `laravel-routing`    | `laravel-api`            |
| Middleware `ValidateDraftToken` — extrai claims do `X-Adesao-Draft-Token` | ⬜     | `laravel-security`   | `laravel-best-practices` |
| FormRequest `SimularAdesaoPublicaRequest` (condicao_id, qtd_parcelas)     | ⬜     | `laravel-validation` | `laravel-dtos`           |
| Pest feature: 200 com draft válido, 401 sem token, 422 condição inválida  | ⬜     | `pest-testing`       | `laravel-testing`        |
| Pest unit: `SimularParcelasAction` cobertura completa de regras           | ⬜     | `pest-testing`       | `php-best-practices`     |

**Critério de aceite S2.1:**

- [ ] `POST /api/v1/adesao/publico/simular` retorna 200 com plano válido
- [ ] Header `X-Adesao-Draft-Token` obrigatório; 401 sem ele
- [ ] PIX exige `qtd_parcelas=1`; Cartão proíbe 1x; Boleto 1-12x escalonado
- [ ] Valores em centavos (INTEGER); arredondamento bancário documentado
- [ ] Cobertura mínima 90% em `SimularParcelasAction`

---

## Sprint S2.2 — Commit atômico + comunicação

> Branch sugerida: `feature/spec010-commit-publico`

| Story                                                                           | Status | Skill primária           | Skill secundária         |
| ------------------------------------------------------------------------------- | ------ | ------------------------ | ------------------------ |
| SPEC-F-002 reescrita: payload `responsavel_cadastro` + `responsavel_financeiro` | ⬜     | `laravel-dtos`           | `php-best-practices`     |
| SPEC-F-007 reescrita: tabela `termos` + `aceites_termo` + snapshot              | ⬜     | `laravel-models`         | `laravel-best-practices` |
| Migrations: `termos`, `aceites_termo`, ajustes em `adesoes`                     | ⬜     | `laravel-models`         | `laravel-best-practices` |
| `CommitAdesaoPublicaAction` em `DB::transaction` (cria 5 entidades)             | ⬜     | `laravel-actions`        | `laravel-services`       |
| Snapshot imutável (pacote, programação, condição) em colunas JSONB da `adesoes` | ⬜     | `laravel-models`         | `php-best-practices`     |
| `AutoLoginTokenService` (15min, uso único, hash em DB)                          | ⬜     | `laravel-security`       | `php-best-practices`     |
| `EnviarConfirmacaoAdesaoMailable` (link "não fui eu" 72h)                       | ⬜     | `laravel-best-practices` | `laravel-services`       |
| `CommitAdesaoPublicaController` + FormRequest                                   | ⬜     | `laravel-routing`        | `laravel-validation`     |
| Idempotência via `X-Idempotency-Key` (commit não duplica em retry)              | ⬜     | `laravel-best-practices` | `laravel-security`       |
| Disparar `PaymentManager::createIntent()` (gateway fake) ao final do commit     | ⬜     | `laravel-services`       | `laravel-best-practices` |
| Pest feature: 201 happy path, 409 CPF existente, 422 termo expirado             | ⬜     | `pest-testing`           | `laravel-testing`        |
| Pest feature: idempotência (mesmo X-Idempotency-Key não duplica)                | ⬜     | `pest-testing`           | `laravel-testing`        |

**Critério de aceite S2.2:**

- [ ] `POST /api/v1/adesao/publico/commit` cria PortalUser + Formando + Adesao + Parcelas + AceiteTermo em 1 transação
- [ ] Retorna `auto_login_token` (15min) + `intent_id` do gateway
- [ ] Email transacional disparado (Mailpit em dev)
- [ ] Retry com mesma `X-Idempotency-Key` retorna 200 (não 201) com payload do primeiro
- [ ] Timeline: `audit_logs` registra `adesao.criada_publica` (SPEC-F-011)

---

## Sprint S2.3 — SPA React 19 — Wizard Público

> Branch sugerida: `feature/spec010-spa-wizard`

| Story                                                                            | Status | Skill primária            | Skill secundária          |
| -------------------------------------------------------------------------------- | ------ | ------------------------- | ------------------------- |
| Bootstrap `resources/spa/` (Vite + React 19 + TS strict)                         | ⬜     | `laravel-best-practices`  | `tailwindcss-development` |
| TanStack Router v1: rotas `/adesao`, `/adesao/$codigo`, `/adesao/$codigo/wizard` | ⬜     | `laravel-best-practices`  | `php-best-practices`      |
| `api/types.gen.ts` via `openapi-typescript` (skeleton em `docs/api/`)            | ⬜     | `laravel-best-practices`  | `laravel-best-practices`  |
| `api/client.ts` Axios (Sanctum cookie + CSRF + interceptor 401)                  | ⬜     | `laravel-security`        | `laravel-best-practices`  |
| Etapa 1 — Validar código (input + GET `/publico/{codigo}`)                       | ⬜     | `tailwindcss-development` | `laravel-best-practices`  |
| Etapa 2 — Escolher curso+período (lista de turmas)                               | ⬜     | `tailwindcss-development` | `laravel-best-practices`  |
| Etapa 3 — Escolher pacote formatura (cards com benefícios)                       | ⬜     | `tailwindcss-development` | `laravel-best-practices`  |
| Etapa 4 — Quem é você + dados pessoais + CPF (POST `/iniciar`)                   | ⬜     | `laravel-validation`      | `tailwindcss-development` |
| Etapa 5 — Plano de pagamento + termos + revisão (POST `/simular` + `/commit`)    | ⬜     | `tailwindcss-development` | `laravel-best-practices`  |
| `wizard-store` Zustand v5 (sessionStorage; nunca localStorage)                   | ⬜     | `php-best-practices`      | `laravel-best-practices`  |
| Auto-login pós-commit + redirect `/portal/pagamento/:intent_id`                  | ⬜     | `laravel-security`        | `laravel-best-practices`  |
| Vitest + Pest browser tests para funnel happy path                               | ⬜     | `pest-testing`            | `laravel-testing`         |

**Critério de aceite S2.3:**

- [ ] Funil completo executável em browser (Mailpit visível para confirmar email)
- [ ] `draft_token` em sessionStorage (NUNCA localStorage)
- [ ] Header `X-Adesao-Draft-Token` enviado em todas as chamadas pós-`iniciar`
- [ ] Retomada após refresh F5 funciona em qualquer etapa
- [ ] Pest browser test: feliz caminho passa do código ao redirect de pagamento

---

## Skills por domínio

### Obrigatórias

| Skill                     | Domínio                                                            |
| ------------------------- | ------------------------------------------------------------------ |
| `laravel-best-practices`  | Padrões gerais (strict_types, type hints, controllers thin)        |
| `laravel-actions`         | `SimularParcelasAction`, `CommitAdesaoPublicaAction`               |
| `laravel-dtos`            | `SimulacaoData`, `ResponsavelData`, `CommitAdesaoData`             |
| `laravel-validation`      | FormRequests com regras de PIX/Boleto/Cartão                       |
| `laravel-security`        | Middleware `ValidateDraftToken`, idempotência, auto-login          |
| `laravel-routing`         | Rotas `/publico/simular`, `/publico/commit` + route names          |
| `laravel-services`        | `AutoLoginTokenService`, mailable, integração `PaymentManager`     |
| `pest-testing`            | Feature tests + browser tests + dataset para regras de parcela     |
| `tailwindcss-development` | SPA UI mobile-first (Tailwind v4 + Inspinia tokens onde aplicável) |
| `php-best-practices`      | Readonly DTOs, transações, snapshot imutável                       |

### Opcionais/situacionais

| Skill                     | Quando usar                                                 |
| ------------------------- | ----------------------------------------------------------- |
| `laravel-owasp-security`  | Hardening do `iniciar` + rate-limit por IP                  |
| `superpowers:tdd`         | TDD obrigatório para Action de cálculo de parcelas          |
| `adr-skill`               | Registrar decisão sobre snapshot JSONB vs colunas dedicadas |
| `eloquent-best-practices` | Queries do `commit` (eager loading + lock)                  |
| `livewire-development`    | NÃO USAR (portal é React SPA — Livewire é só admin)         |

---

## Critérios de aceite da fase S2

- [ ] `POST /api/v1/adesao/publico/simular` operacional + 90% cobertura
- [ ] `POST /api/v1/adesao/publico/commit` operacional + idempotente
- [ ] SPA React em `resources/spa/` rodando em `/adesao`
- [ ] Funil ponta a ponta validado: código → curso → pacote → CPF → plano → termo → confirmar
- [ ] Email "não fui eu" disparado no commit (visível em Mailpit)
- [ ] Auto-login redirect funciona para `/portal/pagamento/:intent_id`
- [ ] Suite de testes 100% verde (atual ~390 + novos S2)
- [ ] CI verde no GitHub Actions
- [ ] Nenhum dado sensível em localStorage; tokens só em sessionStorage ou httpOnly cookie

---

## Notas e decisões prévias

- **Snapshot imutável na adesão**: pacote, programação, condição_pagamento e termo são "fotografados" em colunas JSONB de `adesoes` no commit — nunca referenciados dinamicamente depois. Mesmo se o admin alterar o pacote, a adesão preserva o valor original.
- **Idempotência do commit**: chave armazenada em Redis com TTL 24h. Resposta gravada em cache por chave.
- **Auto-login**: token gerado é hash em `auto_login_tokens` (não JWT), uso único, marcado consumido na primeira request. Falha silenciosa se expirado → fallback para login normal.
- **`draft_token` é HS256** (claims: `cpf_hash`, `contrato_ulid`, `turma_ulid`, `pacote_ulid`, `jti`, `exp`). Persistir hash em `portal_users.draft_token_hash` apenas após commit (auditoria).
- **Email "não fui eu"**: link assinado (`URL::temporarySignedRoute`) com TTL 72h. Cancela adesão + `payment_intent` se executado.
- **Timeline**: registrar `adesao.criada_publica` em `audit_logs` (mesmo padrão de SPEC-F-011) com `causer = portal_user_id` recém-criado.

---

## Riscos e mitigações

| Risco                                               | Probab. | Mitigação                                                      |
| --------------------------------------------------- | :-----: | -------------------------------------------------------------- |
| Cálculo de parcelas com bug de arredondamento       |  média  | TDD com dataset cobrindo 30+ casos; `intdiv` + último ajuste   |
| Race condition no commit (CPF criado 2x)            |  baixa  | UNIQUE em `portal_users.cpf_hash` + lock pessimista            |
| SPA bundle muito grande para mobile 3G              |  média  | code-split por rota; orçamento de 200KB JS gz                  |
| Auto-login token vazado (logs, sentry)              |  alta   | NUNCA logar token completo; só `jti`; sentry beforeSend filter |
| Email rejeitado pelo provedor (alta taxa de bounce) |  baixa  | usar SES + DKIM + SPF; monitorar via Pulse                     |

---

## Próxima fase prevista

**S3 — Portal Autenticado** (após S2): SPEC-001 (login), SPEC-002 reescrito (wizard autenticado reutilizando Actions), SPEC-009 (perfil + área do formando). Reaproveita ~70% das Actions de S2.

_Gerado pela skill `squad-configurator` baseado no plano consolidado de 2026-04-25._
