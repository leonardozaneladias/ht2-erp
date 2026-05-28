---
title: Reconstrução Documental v1.0.0 — Hub unificado, governança e cronograma
version: 1.0.0
date: 2026-04-20
status: draft
author: brainstorming session 2026-04-20 (solo + agentes)
sprint_baseline: pre-F1
owner_role: architect
last_reviewed: 2026-04-20
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: []
change_during_sprint: false
---

# Design — Reconstrução Documental v1.0.0

> Artefato do brainstorming de 2026-04-20. Consolida 7 seções de decisões sobre a nova base documental unificada do Portal ArtFinal v2. Substitui o hub `docs/README.md` v2.0.0 (arquivado em `_archive/legacy-hub/`) por uma estrutura de 16 docs numerados com governance explícita, operating model para solo+agentes, plano de entrega por fatias verticais e cronograma de reconstrução em 5 dias úteis.
>
> O plano de execução concreto (writing-plans) deriva deste spec e vive em `docs/superpowers/plans/2026-04-20-reconstrucao-documental-plan.md` (a ser criado após aprovação deste design).

> **[ATUALIZAÇÃO 2026-04-23]** Documentos de **contexto obrigatório** lidos antes de qualquer trabalho neste repositório:
>
> - [`docs/META/PROJECT-STATUS.md`](../../META/PROJECT-STATUS.md) — status operacional atual (`desenvolvimento`/`homologacao`/`producao`). Governa quais práticas e restrições se aplicam.
> - [`CLAUDE.md`](../../../CLAUDE.md) — contexto geral do projeto, stack, convenções.
>
> Pré-requisito "Dia 0" da reconstrução: ajuste documental do fluxo de adesão pública (Contrato como agregado raiz, código público em `contratos.codigo_acesso`, categoria de pacote formatura/extra). Ver `/Users/leonardozaneladias/.claude/plans/glowing-riding-yao.md` (plano externo). Docs afetados: [SPEC-F-001 v0.3.0](../../features/foundation/SPEC-F-001-contrato-e-turma.md), [SPEC-010 v2.0.0](../../features/SPEC-010-adesao-publica-codigo-contrato.md), [SPEC-002 v2.0.0](../../features/SPEC-002-wizard-adesao.md) (needs-rewrite), [data-model.md](../../data/data-model.md).

---

## 1. Contexto

### 1.1 Situação documental pré-reconstrução

`docs/` contém um ecossistema maduro (~29 pastas/arquivos raiz) construído incrementalmente entre 2026-04-09 e 2026-04-20:

- Hub `docs/README.md` v2.0.0 (2026-04-19) — mapa de 12 seções
- `docs/SPEC-RESTRUCTURE-PLAN.md` — plano ativo de reorganização em 3 camadas, 125 SP totais
- `docs/features/` — 9 SPECs de feature + 11 Foundation SPECs (F-001 a F-011)
- `docs/features/SPEC-010-adesao-publica-codigo-contrato.md` em `plan-ready`
- `docs/architecture/` — SAD arc42 (55KB) + 14 ADRs (ADR-0001..0014) + technical designs
- `docs/prd/` — PRD v4 (38KB) + PLANEJAMENTO_BACKEND_APIV1 (134KB) + PLANEJAMENTO_FRONTEND_REACT + ROADMAP + REGRAS_NEGOCIO + SEGURANCA
- `docs/product/` — PROJECT_BRIEF, PRD_EXPANDED, SRS, journeys-personas, user-flows, macro-screens
- `docs/api/` — api-contract, api-conventions, error-envelope, integrations, openapi-skeleton.yaml
- `docs/frontend/` — 14 docs numerados (00-14) já no padrão que a reconstrução adota
- `docs/modules/` — 20 módulos backend
- `docs/qa/` — qa-strategy, acceptance-criteria, critical-scenarios, nfr-tests, test-plan
- `docs/devops/` — 13 docs (dev-setup, ci-cd, runbooks, monitoring-alerts, security-operations)
- `docs/squads/SQUAD-F1.md` — squad formalizado para F1
- `docs/stories/` — 14 STORY-NNN + sprint-plan-f1 (Sprint 1.1/1.2)
- `docs/roadmap/BACKLOG_FUTURO.md` — backlog de v2 (seating, enquetes)
- `docs/superpowers/specs/` — 2 design docs prévios (2026-04-09, 2026-04-19)
- `docs/superpowers/plans/` — 2 implementation plans prévios
- `docs/template/INSPINIA/` — catálogos e mapas de componentes
- `docs/_archive/` — obsoletos preservados (PRD v3.1.0 + SPEC-006 + SPEC-008 em `future/`)

### 1.2 Problema

Três tensões identificadas na auditoria inicial:

1. **Governance implícita.** Não há doc formal de _documentation governance_ — regras de freeze, rolling-wave, versionamento e ADR lifecycle estão espalhadas por CLAUDE.md, SPEC-RESTRUCTURE-PLAN.md e convenções tácitas. Em solo+agentes isto vira drift.

2. **Papéis de processo não-formalizados.** Product Manager e Scrum Master são invocados via skills BMAD (`/product-manager`, `/scrum-master`) mas sem doc que defina quando acionar, com quais inputs, para produzir quais artefatos. Squad topology só existe para F1 (SQUAD-F1.md) — não há mapa geral BE↔FE cross-phase.

3. **Vertical slice não-explicitado.** Princípio declarado ("BE e FE sempre na mesma sprint, cliente testa a cada sprint") não tem doc operacional. F1 é pura infraestrutura sem feature visível — não encaixa no princípio sem decisão explícita de aceitar F1 como debt-payment.

### 1.3 Gatilho

Usuário solicitou orquestração: auditar, limpar, reconstruir documentação; configurar squad; formalizar PM/SM; preparar base para iniciar desenvolvimento por ciclos/sprints com teste contínuo. Lista literal de 16 documentos numerados (00-16) a serem criados em `docs/`.

---

## 2. Decisões macro (4 estratégicas)

### 2.1 Estratégia: Reconstruir (opção B)

**Escolhida: B — Reconstruir estrutura nova com 16 docs numerados em `docs/` raiz.**

Alternativas descartadas:

- **A — Evoluir** (só criar os 6 gap docs dentro da estrutura existente): rejeitada pelo usuário; preferência por nova estrutura visível.
- **C — Híbrido em 2 ondas** (gaps agora, reorganização pós-F1): rejeitada pelo usuário; preferência por reconstrução imediata.

### 2.2 Timing: Adiar Sprint F1 (opção B.1)

**Escolhida: B.1 — Arranque da Sprint F1.1 adiado para qua 2026-04-29** (originalmente 2026-04-21).

Janela de reconstrução: qua 2026-04-22 → ter 2026-04-28 (5 dias úteis, pula fim de semana 26-27/04).

Alternativas descartadas:

- **B.2 — F1 em paralelo com `docs-v2/`**: rejeitada; gera duas fontes de verdade por 2 semanas.
- **B.3 — Reconstrução pós-Sprint 1.1**: rejeitada; usuário quer tudo pronto antes de F1.
- **B.4 — Reconstrução imediata com legado em `_archive`**: rejeitada; UX ruim para time durante Sprint F1.

### 2.3 Squad: Solo + agentes (opção A)

**Escolhida: A — 1 humano (`leonardozaneladias`) + agentes Claude Code/BMAD.**

Impacto nos docs de processo: operating models são **manuais operacionais** (como acionar agentes, inputs/outputs, checklists) em vez de RACI corporativo. Cerimônias são **assíncronas e checklist-driven**.

Alternativas descartadas:

- B/C/D — times humanos reais (não é a realidade).
- E — híbrido agente-dirigido com cliente como stakeholder (cliente participa só em demos de slice, não em cerimônias).

### 2.4 Conteúdo dos docs unificados: Thin Index + Delta (opção CS.1)

**Escolhida: CS.1 — docs 05-11 e 13-15 são índices curtos (~200-500 linhas) que apontam para fontes de verdade existentes.**

Princípio: zero re-escrita de conteúdo consolidado. Fontes legadas (`product/`, `prd/`, `architecture/`, `api/`, `modules/`, `frontend/`, `qa/`, `devops/`) permanecem intactas. Thin indexes agregam "o que você precisa saber antes de consultar a fonte" + deltas (gaps, erratas, mudanças) + marcações explícitas (confirmado / inferido / pendente / obsoleto).

Alternativas descartadas:

- **CS.2 — Merge & Move**: rejeitada; re-escrita massiva; atrasa 3-5 dias além dos 5 previstos.
- **CS.3 — Full Rewrite**: rejeitada; 1-2 semanas; risco real de perder nuance.

---

## 3. Arquitetura física

### 3.1 Estrutura resultante

```
docs/
├── 00-INDEX.md                           ← novo hub mestre (substitui README.md)
├── 01-DOCUMENTATION-GOVERNANCE.md        ← NOVO (gap real)
├── 02-PRODUCT-OPERATING-MODEL.md         ← NOVO (gap real)
├── 03-SCRUM-OPERATING-MODEL.md           ← NOVO (gap real)
├── 04-SQUAD-TOPOLOGY.md                  ← NOVO (gap real)
├── 05-UNIFIED-PROJECT-BRIEF.md           ← THIN INDEX → product/PROJECT_BRIEF.md
├── 06-UNIFIED-PRD.md                     ← THIN INDEX → prd/PRD_v4.md + product/PRD_EXPANDED.md
├── 07-UNIFIED-SRS.md                     ← THIN INDEX → product/SRS.md + prd/REGRAS_NEGOCIO + SEGURANCA
├── 08-UNIFIED-SAD-ARC42.md               ← THIN INDEX → architecture/SAD-arc42.md
├── 09-ADR-INDEX.md                       ← THIN INDEX → architecture/adrs/ (14 ADRs)
├── 10-API-BACKEND-INDEX.md               ← THIN INDEX → api/ + modules/ + prd/PLANEJAMENTO_BACKEND_APIV1.md
├── 11-FRONTEND-REACT-INDEX.md            ← THIN INDEX → frontend/ + prd/PLANEJAMENTO_FRONTEND_REACT.md
├── 12-VERTICAL-SLICE-DELIVERY-PLAN.md    ← NOVO (gap real)
├── 13-QA-AND-ACCEPTANCE-STRATEGY.md      ← THIN INDEX → qa/
├── 14-DEV-SETUP-AND-WORKFLOW.md          ← THIN INDEX → devops/dev-setup + conventions + ci-cd
├── 15-RUNBOOK.md                         ← THIN INDEX → devops/runbook-deploy + runbook-operations
├── 16-OPEN-QUESTIONS-AND-BLOCKERS.md     ← NOVO (gap real)
├── reports/
│   └── 2026-04-20-AUDIT-REPORT.md        ← NOVO (entregável do Dia 1)
│
├── _archive/
│   ├── PRD_Sistema_Formatura_v3.1.0.md   ← (existente, preservado)
│   ├── future/                           ← (existente, SPEC-006, SPEC-008)
│   └── legacy-hub/
│       └── README-v2.0.0-2026-04-19.md   ← NOVO — snapshot do README atual
│
├── product/                              ← PRESERVADA
├── prd/                                  ← PRESERVADA
├── architecture/                         ← PRESERVADA
├── api/                                  ← PRESERVADA
├── data/                                 ← PRESERVADA
├── features/                             ← PRESERVADA (3 camadas intactas)
├── modules/                              ← PRESERVADA (20 módulos)
├── frontend/                             ← PRESERVADA (14 docs 00-14)
├── qa/                                   ← PRESERVADA
├── devops/                               ← PRESERVADA
├── squads/                               ← PRESERVADA + link bi-direcional com 04-SQUAD-TOPOLOGY
├── stories/                              ← PRESERVADA (14 stories + sprint-plan-f1)
├── roadmap/                              ← PRESERVADA
├── superpowers/                          ← PRESERVADA (specs/ + plans/)
├── template/                             ← PRESERVADA (INSPINIA)
├── site/                                 ← PRESERVADA (Docsify config)
├── prompts/                              ← PRESERVADA
├── SPEC-RESTRUCTURE-PLAN.md              ← PRESERVADO (complementar ao 01-GOVERNANCE)
├── index.html, _coverpage.md             ← INTOCADOS (Docsify)
├── _sidebar.md                           ← ATUALIZADO (navegação 00-16)
├── _navbar.md                            ← ATUALIZADO (quick-access)
└── README.md                             ← stub de 3 linhas → 00-INDEX.md
```

### 3.2 Princípios de arquitetura física

1. **Zero destruição.** Adição de 16 arquivos novos no root + 1 arquivo de relatório + 1 snapshot. Nenhuma pasta legada movida.
2. **Hub novo `00-INDEX.md`** vira porta de entrada; `README.md` fica como stub redirect (compatibilidade).
3. **Apenas 6 docs têm conteúdo novo** (01, 02, 03, 04, 12, 16). 10 docs são thin indexes.
4. **Pastas existentes são "fontes"** referenciadas pelos thin indexes (via caminho + seção).
5. **Sidebar/navbar Docsify** atualizados para mostrar a numeração 00-16.
6. **SPEC-RESTRUCTURE-PLAN.md continua vivo** — complementa o 01-GOVERNANCE como política específica de evolução de SPECs.

---

## 4. Documentation Governance (doc 01)

### 4.1 Esqueleto do `01-DOCUMENTATION-GOVERNANCE.md`

1. Princípios — docs-as-code, docs vivas, SSOT por tópico, rastreabilidade, rolling-wave
2. Ciclo de vida do documento — `stub → draft → active → refactor-pending → superseded/archived` (alinhado ao SPEC-RESTRUCTURE-PLAN)
3. Freeze de sprint — baseline congelada durante sprint ativa; mudanças viram backlog, ADR ou próxima sprint
4. Rolling-wave planning — N+0 frozen / N+1 draft / N+2 sketch / N+3+ ideias
5. Versionamento — frontmatter `version` SemVer + snapshot via git tag por sprint
6. Rituais documentais — pré-sprint / durante / pós-sprint (consolidação obrigatória)
7. Fluxo de mudança — PR com label `docs-change` → review → merge
8. ADR lifecycle — quando criar, template, superseding, deprecation
9. Destino de docs antigos — archived vs superseded vs merged + regras de frontmatter
10. Enforcement — agentes + pre-commit + auditoria trimestral

### 4.2 Decisões aprovadas

| Item                         | Decisão                                                                                                                     |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **Freeze enforcement**       | **A.2 — Agent-checked.** Agente `scrum-master` faz check pré-PR; bloqueia alteração em arquivo frozen durante sprint ativa. |
| **Rolling-wave horizon**     | **B.2 — Médio.** N e N+1 detalhadas; N+2 em esboço; N+3+ ideias.                                                            |
| **Versionamento**            | **C.1 — SemVer** (`MAJOR.MINOR.PATCH`), alinhado ao padrão já usado em SPEC-RESTRUCTURE-PLAN.                               |
| **ADR triggers**             | **D.2 — Decisões caras de reverter + convenções cross-module** (padrão dos 14 ADRs existentes).                             |
| **Snapshots por sprint**     | **E.2 — Git tag apenas** (`sprint-NN-baseline`), zero overhead de disco.                                                    |
| **Quem pode quebrar freeze** | **F.1 — PM role + ADR obrigatório** (`ADR-NNNN: Quebra de freeze Sprint X — razão Y`).                                      |

### 4.3 Frontmatter padrão unificado

```yaml
---
title: <título legível em PT-BR>
version: 1.0.0 # SemVer
date: 2026-04-20 # YYYY-MM-DD
status: draft # stub | draft | active | refactor-pending | superseded | archived
sprint_baseline: F1-sprint1.1 # sprint em que este doc foi congelado como baseline (null se não frozen)
owner_role: product-manager # product-manager | scrum-master | developer | architect | qa
last_reviewed: 2026-04-20 # data do último review
review_cadence: pre-sprint # pre-sprint | on-change | quarterly
supersedes: null # path do doc substituído (se aplicável)
superseded_by: null # path do doc que substituiu (se archived)
related_adrs: [] # [ADR-0007, ADR-0014]
related_features: [] # [SPEC-002, SPEC-F-001]
change_during_sprint: false # pode mudar durante sprint ativa? (default false)
---
```

---

## 5. Operating Model (docs 02, 03, 04)

### 5.1 `02-PRODUCT-OPERATING-MODEL.md` — esqueleto

1. Propósito do papel (visão, valor, priorização, backlog, critérios de aceite, governança de mudança)
2. Agentes que ocupam o papel — `/product-manager` (BMAD) + `/bmad-orchestrator` + intervenção humana (você) para calls estratégicos
3. Inputs que o papel produz/mantém — PRD, user stories, acceptance criteria, DoR checklist, priorização do backlog, change log de escopo
4. Quando acionar (6 gatilhos concretos) — (a) nova feature na visão, (b) refinamento pré-sprint, (c) mudança de escopo mid-sprint, (d) validação de acceptance criteria pós-dev, (e) alinhamento com cliente, (f) consolidação pós-sprint
5. Definition of Ready (DoR) — checklist que toda story precisa antes de entrar em sprint
6. Change control fora da sprint — fluxo de mudança → backlog → próxima sprint ou ADR
7. Artefatos mantidos — `BACKLOG_FUTURO.md`, `PRD_v4.md`, `SRS.md`, acceptance criteria em cada SPEC
8. Handoff com Scrum Master — DoR é o contrato entre PM e SM

### 5.2 `03-SCRUM-OPERATING-MODEL.md` — esqueleto

1. Propósito do papel (proteger Sprint Goal, freeze, cadência, impedimentos, disciplina pós-sprint)
2. Agentes que ocupam o papel — `/scrum-master` (BMAD) + `/squad-configurator` + intervenção humana para judgment calls
3. Rituais assíncronos (5 rituais checklist-driven):
    - **Planning** (pré-sprint) — congela baseline, estima stories, aloca capacity, registra Sprint Goal
    - **Daily self-check** (diário) — `TaskList` para ver progresso, identificar impedimentos
    - **Pre-PR Review** (pré-merge) — DoD check + `pr-review-toolkit:code-reviewer` + `pr-test-analyzer` + `silent-failure-hunter`
    - **Sprint Review** (fim da sprint) — demo ao cliente via app funcional (vertical slice) + validação acceptance criteria
    - **Retrospective + Docs Consolidation** (fim da sprint) — lições, atualizar baseline, fechar ADRs pendentes
4. Enforcement do freeze — checklist pré-PR valida que arquivos frozen não foram tocados
5. Impedimentos — classificação (técnico / blocker externo / scope creep) + escalação ao PM role
6. Definition of Done (DoD) — checklist que toda story precisa antes de fechar
7. Artefatos mantidos — `sprint-plan-FN.md` por fase, git tags, retrospective notes
8. Handoff com Product Manager — DoD é o contrato entre SM e PM

### 5.3 `04-SQUAD-TOPOLOGY.md` — esqueleto

Topologia: 1 humano (você — orquestrador) + N agentes em 4 lanes virtuais:

```
                    ┌─── BE Lane (Laravel)
                    │    laravel-specialist, laravel-patterns, laravel-api,
                    │    laravel-testing, laravel-security, laravel-architecture,
                    │    eloquent-best-practices, pest-testing, php-best-practices
                    │
você (orquestrador) ├─── FE Lane (React SPA)
                    │    react-patterns, react-state-management, react-best-practices,
                    │    react-ui-patterns, frontend-design, tailwindcss-development,
                    │    building-components, vercel-react-best-practices
                    │
                    ├─── Cross-cutting (Processo + Arquitetura)
                    │    product-manager, scrum-master, bmad-orchestrator,
                    │    squad-configurator, adr-skill, feature-dev:code-architect,
                    │    feature-dev:code-explorer, api-design-principles
                    │
                    └─── QA + Review
                         pr-review-toolkit:code-reviewer, pr-test-analyzer,
                         silent-failure-hunter, type-design-analyzer,
                         superpowers:verification-before-completion, playwright
```

Sincronização BE↔FE intra-sprint — **contrato OpenAPI é o ponto de sync**:

- Dia 1-2 da sprint: `api-design-principles` + BE lane definem/atualizam `docs/api/openapi-skeleton.yaml`
- Dia 2: FE lane roda `openapi-typescript` codegen → `resources/spa/src/api/types.gen.ts`
- Dia 3+: BE e FE trabalham em paralelo contra o contrato congelado
- Pré-PR: validação cruzada E2E via `playwright`

Regras de acoplamento:

- BE e FE **sempre** na mesma sprint (vertical slice)
- Contrato OpenAPI é frozen até fim da sprint
- Se contrato muda mid-sprint → ADR + PM review + quebra de freeze (F.1)

### 5.4 Decisões aprovadas

| Item                          | Decisão                                                                                                                            |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Cadência de sprint**        | **G.1 — 2 semanas (10 dias úteis)**, alinhada com `sprint-plan-f1.md`.                                                             |
| **Sincronização BE↔FE**      | **H.1 — Contrato-first.** OpenAPI congelado dia 1-2; lanes paralelas após codegen.                                                 |
| **DoR específicos**           | **I.1 — Acceptance Gherkin + contrato API definido + dependências mapeadas + SPs estimados.**                                      |
| **DoD específicos**           | **J.1 — Código + testes + `pint` + `phpstan` level 6 + `prettier` + review-agent + PR aprovado** (herdado de `sprint-plan-f1.md`). |
| **Retrospective obrigatória** | **K.1 — Sim.** Toda sprint termina com atualização de baseline, fechamento de ADRs, registro de lições.                            |
| **Agente driver da sprint**   | **L.1 — `bmad-orchestrator`** começa, aciona `scrum-master` → `developer` conforme ciclo.                                          |

---

## 6. Vertical Slice Delivery Plan (doc 12)

### 6.1 Esqueleto do `12-VERTICAL-SLICE-DELIVERY-PLAN.md`

1. Definição de vertical slice — feature end-to-end em 1 sprint: migration + model + service + controller + FE page/hook + E2E playwright + demo testável
2. Princípios — contrato-first, BE↔FE na mesma sprint, cliente testa ao fim, feature flag se atrasar
3. Ciclo canônico de 10 dias da sprint (ver 6.2)
4. Roadmap de slices por fase (ver 6.3)
5. Matriz de dependências entre slices
6. Capacidade por sprint — 17 SP baseline + 2-3 SP cross-cutting
7. Como o cliente testa cada slice — ambiente acessível + checklist
8. Feature flags — quando slice não fecha: feature oculta, ADR de dívida
9. Rollback conceitual — como reverter slice rejeitado

### 6.2 Ciclo canônico de 10 dias

```
Dia 1    Planning (SM) — DoR, freeze, capacity, Sprint Goal
Dia 1-2  Contrato OpenAPI definido/atualizado (sync BE↔FE)
Dia 2    Codegen rodado → FE lane destrava
Dia 2-7  Dev paralelo BE+FE contra contrato congelado
Dia 8    Integração + E2E (playwright)
Dia 9    DoD review + PR + demo cliente
Dia 10   Buffer + retrospective + consolidação documental
```

### 6.3 Roadmap proposto de slices

| Fase   | Slice                                                   | Sprint(s) | Demo ao cliente                                           | SPECs envolvidos                                | SP  |
| ------ | ------------------------------------------------------- | --------- | --------------------------------------------------------- | ----------------------------------------------- | --- |
| **F1** | _Infra only_ (sem demo de cliente)                      | 1.1, 1.2  | healthcheck + `/me` + arch tests verdes                   | STORY-001..014                                  | 34  |
| **F2** | Slice 1 — Adesão pública (Gate Pre-0 → Gate 2)          | 2.1, 2.2  | formando usa código da turma e conclui adesão pública E2E | SPEC-010 (parcial) + SPEC-F-001/002/003/009/010 | ~21 |
| **F3** | Slice 2 — Login portal + Wizard autenticado             | 3.1, 3.2  | formando loga, vê adesão gerada em F2, conclui wizard     | SPEC-001, SPEC-002, SPEC-F-004/005/006/007      | ~20 |
| **F4** | Slice 3 — Pagamento PIX + Boleto                        | 4.1, 4.2  | formando paga 1ª parcela PIX, vê status em tempo real     | SPEC-003, SPEC-F-009                            | ~17 |
| **F5** | Slice 4 — Convites + RSVP                               | 5.1       | formando gera convites; convidado responde RSVP           | SPEC-004, SPEC-005                              | ~13 |
| **F6** | Slice 5 — Extras + Perfil                               | 6.1       | formando compra extra, edita perfil                       | SPEC-007, SPEC-009                              | ~13 |
| **F7** | Slice 6 — Admin (contratos, pacotes, termos, formandos) | 7.1, 7.2  | empresa gerencia contrato da turma de F2                  | SPEC-011, 012, 013, 014                         | ~26 |
| **F8** | Slice 7 — E-mails + hardening                           | 8.1       | e-mails transacionais + melhorias cross-cutting           | SPEC-015 + débito ADRs                          | ~13 |

**Total:** ~157 SP em ~13 sprints → ~26 semanas (~6 meses de execução).

### 6.4 Decisões aprovadas

| Item                        | Decisão                                                                                                                                                                           |
| --------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **F1 sem slice visível**    | **M.1 — F1 aceita como infra-only.** Demo ao cliente = "aguarde F2". Sprint Review F1 é técnica (você + agentes validando critérios). Documenta-se como debt-payment obrigatório. |
| **Granularidade de slice**  | **N.1 — Um SPEC pode ocupar múltiplas sprints.** SPEC-010 vira F2 (sprints 2.1+2.2, ~21 SP crítico). Resto para F3 integrado com wizard.                                          |
| **Capacidade por sprint**   | **O.1 — 17 SP baseline + 2-3 SP cross-cutting**, total ~20 SP efetivo.                                                                                                            |
| **Ambiente cliente testar** | **P.1 — Docker Compose local + tunnel (`ngrok`)** ao fim de cada sprint; link temporário 48h. Zero infra de staging cloud.                                                        |
| **Feature flag tool**       | **Q.1 — Laravel Pennant** (oficial Laravel 13) a partir de F3.                                                                                                                    |
| **Sprint 0 formal**         | **R.1 — Não criar.** Janela de 5 dias de reconstrução documental é a preparação. F1 Sprint 1.1 inicia em seguida.                                                                 |

---

## 7. Conteúdo dos 16 docs

### 7.1 Template padrão de THIN INDEX (docs 05-11, 13-15)

```markdown
---
title: <título unificado>
version: 1.0.0
date: 2026-04-20
status: active
sprint_baseline: F1-sprint1.1
owner_role: <product-manager | architect | developer | qa>
last_reviewed: 2026-04-20
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: [ADR-NNNN]
change_during_sprint: false
---

# <Título> — Thin Index

> **Este doc é um índice.** A verdade está nas fontes listadas abaixo.
> Política de evolução: ver [01-DOCUMENTATION-GOVERNANCE](01-DOCUMENTATION-GOVERNANCE.md).

## 1. Propósito

## 2. Fontes de verdade (primárias) — tabela com caminho + seções relevantes + status

## 3. O que você PRECISA saber antes de consultar as fontes — 5-15 bullets executivos

## 4. Marcações explícitas — tabela Confirmado / Inferido / Pendente / Obsoleto

## 5. Delta desde a fonte — novidades, gaps, correções não aplicadas

## 6. Docs relacionados — thin indexes, ADRs, SPECs
```

### 7.2 Conteúdo específico por doc

| Doc                                  | Propósito                                                                                                                                     | Fontes primárias                                                                                                                                                                               | Marcações/deltas esperados                                                                  |
| ------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| **00-INDEX.md**                      | Hub mestre; mapa de leitura por persona; status de cada doc; ordem de leitura; integração com Docsify                                         | (é a capa; aponta para tudo)                                                                                                                                                                   | Substitui `docs/README.md` atual; README vira redirect                                      |
| **05-UNIFIED-PROJECT-BRIEF.md**      | Visão unificada: propósito do sistema, valor para formandos e empresas, macroescopo, fora de escopo, premissas, riscos, objetivos por release | `product/PROJECT_BRIEF.md`, `product/README.md`                                                                                                                                                | Alinhar com SPEC-010 (adesão pública); v1 arquivou seating+enquetes                         |
| **06-UNIFIED-PRD.md**                | Produto unificado: jornadas, features por domínio, priorização por sprint/fase, critérios macro de aceite, dependências BE↔FE                | `prd/PRD_v4.md`, `product/PRD_EXPANDED.md`, `prd/REGRAS_NEGOCIO.md`, `product/journeys-personas.md`, `product/user-flows.md`                                                                   | Marcar bounded contexts seating/enquetes como 🟡 deferred v2; alinhar jornadas com SPEC-010 |
| **07-UNIFIED-SRS.md**                | Requisitos funcionais + não funcionais + integração + auth + paginação + erros + validação + permissões + observabilidade + a11y + perf       | `product/SRS.md`, `prd/REGRAS_NEGOCIO.md`, `prd/SEGURANCA.md`, `prd/PERFORMANCE.md`                                                                                                            | Consolidar NFRs cross-cutting hoje fragmentados em 4 arquivos                               |
| **08-UNIFIED-SAD-ARC42.md**          | Arquitetura arc42 (contexto, blocos, runtime, deploy, crosscutting, riscos, qualidade, dívida)                                                | `architecture/SAD-arc42.md` (55KB, já arc42)                                                                                                                                                   | Pouca edição; destacar decisões críticas + links cruzados                                   |
| **09-ADR-INDEX.md**                  | Lista + status dos 14 ADRs + template para novos + lifecycle                                                                                  | `architecture/adrs/ADR-0001..0014*.md`                                                                                                                                                         | Adicionar tipo especial "ADR-NNNN: Quebra de freeze Sprint X" (F.1)                         |
| **10-API-BACKEND-INDEX.md**          | Contratos OpenAPI · endpoints · convenções · módulos · dependências do FE · critérios de aceite BE                                            | `api/api-contract.md`, `api/openapi-skeleton.yaml`, `api/api-conventions.md`, `api/error-envelope.md`, `api/integrations.md`, `modules/*.md` (20 módulos), `prd/PLANEJAMENTO_BACKEND_APIV1.md` | Link cruzado com vertical slice plan (12-); marcar módulos por fase                         |
| **11-FRONTEND-REACT-INDEX.md**       | Rotas · stores · hooks · componentes · fluxos · estados · integração API · design system · QA por fluxo                                       | `frontend/00-14-*.md` (14 docs numerados), `prd/PLANEJAMENTO_FRONTEND_REACT.md`                                                                                                                | Indexar o que existe; marcar deltas vs planejamento original                                |
| **13-QA-AND-ACCEPTANCE-STRATEGY.md** | Estratégia de testes · testes BE+FE por slice · smoke · regressão · E2E · critérios funcionais                                                | `qa/qa-strategy.md`, `qa/acceptance-criteria.md`, `qa/critical-scenarios.md`, `qa/nfr-tests.md`, `qa/test-plan.md`                                                                             | Alinhar ciclo QA ao fluxo de 10 dias da seção 6.2                                           |
| **14-DEV-SETUP-AND-WORKFLOW.md**     | Setup · convenções · fluxo de branch · revisão · codegen · DoR/DoD                                                                            | `devops/dev-setup.md`, `devops/conventions.md`, `devops/engineering-standards.md`, `devops/ci-cd.md`, `devops/tools-and-packages.md`                                                           | Incluir regra OpenAPI→codegen (H.1)                                                         |
| **15-RUNBOOK.md**                    | Execução local · debug · geração de tipos · validação de integração · troubleshooting · publicação · rollback                                 | `devops/runbook-deploy.md`, `devops/runbook-operations.md`, `devops/monitoring-alerts.md`, `devops/security-operations.md`, `devops/infra.md`                                                  | Adicionar runbook "demo ao cliente via tunnel" (P.1)                                        |

### 7.3 `16-OPEN-QUESTIONS-AND-BLOCKERS.md` — esqueleto (novo, não é thin index)

```markdown
# Open Questions e Blockers — cross-cutting

## 1. Pendências ativas (precisam resposta antes de F2) — tabela Q-NNN / Pergunta / Por quê importa / Responsável / Prazo / Status

## 2. Blockers técnicos — tabela # / Bloqueador / Impacto / Mitigação proposta

## 3. Gaps entre documento e implementação — # / Gap / Doc afetado / SPEC afetado

## 4. Gaps entre BE e FE — # / Gap / Doc BE / Doc FE

## 5. Dúvidas respondidas (histórico) — # / Pergunta / Resposta / Data / ADR
```

### 7.4 Decisões aprovadas

| Item                            | Decisão                                                                                            |
| ------------------------------- | -------------------------------------------------------------------------------------------------- |
| **Tamanho thin indexes**        | **S.1 — Enxuto (~200-500 linhas)**; foco em "o que saber antes de ir à fonte".                     |
| **Personas no 00-INDEX**        | **T.1 — Sim, 5 personas**: você-como-dev, você-como-PM, você-como-SM, agente Claude Code, cliente. |
| **Seed do 16-OPEN-QUESTIONS**   | **U.1 — ~10 questões reais** extraídas da auditoria.                                               |
| **Granularidade de referência** | **V.1 — Seções** (`PRD_v4.md §5.1`), não linhas.                                                   |
| **Versionamento lockstep?**     | **W.1 — Não.** Thin index tem ciclo próprio.                                                       |

---

## 8. Cronograma de reconstrução (5 dias úteis)

### 8.1 Premissas de data

- **Hoje:** seg 2026-04-20 (design approved)
- **Janela:** qua 2026-04-22 → ter 2026-04-28 (5 dias úteis, pula fds 26-27)
- **Sprint F1.1 arranque:** qua 2026-04-29 (adiado +8 dias corridos)

### 8.2 Cronograma diário

| Dia   | Data           | Trabalho principal                                                                                                                    | Commit                                                      |
| ----- | -------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| **1** | qua 2026-04-22 | Auditoria full-pass (`/ln-611-docs-structure-auditor`) → `docs/reports/2026-04-20-AUDIT-REPORT.md` + `01-DOCUMENTATION-GOVERNANCE.md` | `docs(gov): auditoria inicial + governance model v1.0.0`    |
| **2** | qui 2026-04-23 | `02-PRODUCT-OPERATING-MODEL.md` + `03-SCRUM-OPERATING-MODEL.md` (paralelo) + `04-SQUAD-TOPOLOGY.md`                                   | `docs(squad): operating models PM/SM + squad topology`      |
| **3** | sex 2026-04-24 | `12-VERTICAL-SLICE-DELIVERY-PLAN.md` + `16-OPEN-QUESTIONS-AND-BLOCKERS.md` (seed ~10 pendências)                                      | `docs(delivery): vertical slice plan + open questions seed` |
| **4** | seg 2026-04-27 | Thin indexes 05, 06, 07, 08, 09, 10, 11, 13, 14, 15 (10 docs, paralelo massivo Y.1 via agentes Explore)                               | `docs(indexes): thin indexes 05-11, 13-15`                  |
| **5** | ter 2026-04-28 | `00-INDEX.md` hub + Docsify nav + validação links + frontmatter + git tags (`docs-reconstruction-v1.0.0`, `sprint-1.1-baseline`)      | `docs(hub): 00-INDEX + Docsify nav + validação`             |

### 8.3 Entregáveis finais (ao fim do Dia 5)

- 17 arquivos novos em `docs/` (00-INDEX + 01-16 + audit report)
- 1 snapshot em `docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md`
- `docs/README.md` como stub redirect
- `docs/_sidebar.md` + `docs/_navbar.md` atualizados
- 2 git tags: `docs-reconstruction-v1.0.0` e `sprint-1.1-baseline`
- 5 commits (um por dia)
- 1 PR consolidado: _"docs: reconstrução v1.0.0 — hub 00-INDEX + governance + 16 docs unificados"_

### 8.4 Decisões aprovadas

| Item                    | Decisão                                                                                     |
| ----------------------- | ------------------------------------------------------------------------------------------- |
| **Dia 1**               | **X.custom — qua 2026-04-22** (ajuste do usuário; originalmente era ter 2026-04-21).        |
| **Paralelização Dia 4** | **Y.1 — Dispatch 10 agentes Explore paralelos** lendo fontes; consolidação manual em série. |
| **Commits**             | **Z.1 — 1 commit por dia** (5 commits totais + tag final).                                  |
| **Branch**              | **AA.1 — Continuar na branch atual** `feature/planejamento-backend-api-v1`.                 |
| **Aprovação**           | **BB.1 — Daily checkpoint.** Ao fim de cada dia, you approve antes de avançar.              |
| **PR**                  | **CC.1 — PR único ao fim do Dia 5** consolidando tudo.                                      |

---

## 9. Audit Report (Dia 1, manhã)

### 9.1 Caminho

`docs/reports/2026-04-20-AUDIT-REPORT.md` (data do início do design).

### 9.2 Esqueleto

1. Sumário executivo (contagens por classificação)
2. Escopo e metodologia
3. Inventário completo — tabela com caminho / tipo / tamanho / última edição / classificação / motivo / destino
4. Conflitos identificados
5. Redundâncias
6. Órfãos
7. Gaps de rastreabilidade
8. Desalinhamentos BE↔FE
9. Desalinhamentos doc↔código (sampling)
10. Plano de ação
11. Anexos (ADRs, SPECs, grafo de dependências)

### 9.3 Taxonomia de classificação

| Classe              | Significado                                         | Ação                                               |
| ------------------- | --------------------------------------------------- | -------------------------------------------------- |
| `manter`            | Útil, ativo, sem problema                           | Permanece; thin index aponta                       |
| `manter-com-ajuste` | Útil com pequenos problemas (datas, links, erratas) | Correção inline OU registrado em 16-OPEN-QUESTIONS |
| `fundir`            | Deve integrar a outro doc                           | Marcar para fusão pós-F1                           |
| `substituir`        | Doc novo ocupa lugar                                | Legado → `_archive/legacy-hub/`                    |
| `arquivar`          | Obsoleto; preserva histórico                        | Move para `_archive/` com `superseded_by`          |
| `excluir`           | Sem valor histórico                                 | Só com aprovação explícita; default = arquivar     |

---

## 10. Critérios de sucesso

A reconstrução só estará concluída ao fim do Dia 5 quando:

- ✅ 17 arquivos novos criados em `docs/` (00-16 + audit report)
- ✅ Todos com frontmatter válido seguindo padrão §4.3
- ✅ Todos os thin indexes (05-11, 13-15) apontam para fontes existentes via caminho + seção
- ✅ Os 6 gap docs (01, 02, 03, 04, 12, 16) têm conteúdo próprio, não apenas links
- ✅ `docs/README.md` é stub redirect para `00-INDEX.md`
- ✅ `docs/_sidebar.md` e `docs/_navbar.md` renderizam a numeração 00-16 no Docsify
- ✅ Nenhum link quebrado (validado via lychee ou similar)
- ✅ Snapshot do hub legado preservado em `_archive/legacy-hub/`
- ✅ Git tags `docs-reconstruction-v1.0.0` e `sprint-1.1-baseline` criadas
- ✅ PR único aberto consolidando todas as mudanças
- ✅ `01-GOVERNANCE` operacional com freeze enforceable pelo agente `scrum-master`
- ✅ `12-VERTICAL-SLICE-DELIVERY-PLAN` documenta F1 como infra-only (M.1) com demo técnica
- ✅ `16-OPEN-QUESTIONS` populado com seed ~10 pendências reais
- ✅ Documentação continua navegável via Docsify (`npx docsify-cli serve docs`)
- ✅ Sprint F1.1 pode iniciar qua 2026-04-29 com baseline congelada

---

## 11. Riscos identificados

| #   | Risco                                                                                  | Probabilidade | Impacto | Mitigação                                                                                                                                                   |
| --- | -------------------------------------------------------------------------------------- | ------------- | ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R1  | Auditoria Dia 1 descobre conflito grave que exige redesign                             | Baixa         | Alto    | Checkpoint BB.1 diário permite corrigir rumo antes de escalar                                                                                               |
| R2  | Thin indexes do Dia 4 divergem de conteúdo atual por fonte desatualizada               | Média         | Médio   | Marcação explícita de `🟡 Inferido` + validação cruzada via agente                                                                                          |
| R3  | Docsify quebra navegação após update de `_sidebar.md`                                  | Média         | Baixo   | Validação local no Dia 5 antes de push; fácil rollback                                                                                                      |
| R4  | F1 Sprint 1.1 ainda atrasa por qualquer motivo                                         | Média         | Médio   | Cronograma tem folga (Dia 5 inclui validação + buffer)                                                                                                      |
| R5  | Usuário muda de ideia sobre algum default aprovado                                     | Baixa         | Médio   | BB.1 daily checkpoint cria oportunidade de pivot antes de cascatear                                                                                         |
| R6  | Agente scrum-master não consegue implementar freeze enforcement técnico                | Média         | Médio   | Fallback: enforcement como convenção escrita (A.1) em vez de A.2; registrar ADR da decisão                                                                  |
| R7  | `ngrok`/tunnel não funciona para demo ao cliente em F2                                 | Baixa         | Baixo   | Alternativa pré-gravada em vídeo + doc de troubleshooting em 15-RUNBOOK                                                                                     |
| R8  | Confusão entre `docs/SPEC-RESTRUCTURE-PLAN.md` e `docs/01-DOCUMENTATION-GOVERNANCE.md` | Média         | Baixo   | 01-GOVERNANCE tem seção dedicada explicando a divisão: governance = política geral de docs; SPEC-RESTRUCTURE-PLAN = política específica de SPECs de feature |

---

## 12. Próximos passos

Imediatamente após aprovação deste design:

1. Passar por self-review (placeholders, consistência, escopo, ambiguidade)
2. Submeter à revisão do usuário (gate obrigatório antes de partir para plano)
3. Invocar `superpowers:writing-plans` para criar o plano executável em `docs/superpowers/plans/2026-04-20-reconstrucao-documental-plan.md`
4. Plano consolidará os 5 dias em batches com TDD-first onde aplicável, checkpoints explícitos, e validação de cada entregável
5. Após aprovação do plano, invocar `superpowers:executing-plans` para executar o Dia 1 qua 2026-04-22

---

## 13. Referências

- [`docs/README.md`](../../README.md) — hub atual a ser substituído
- [`docs/SPEC-RESTRUCTURE-PLAN.md`](../../SPEC-RESTRUCTURE-PLAN.md) — plano de SPECs ativo (complementar)
- [`docs/superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md`](2026-04-19-reorganizacao-specs-adesao-publica-design.md) — design prévio (SPECs + SPEC-010)
- [`docs/superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md`](../plans/2026-04-19-adesao-publica-codigo-contrato-plan.md) — plano SPEC-010
- [`docs/squads/SQUAD-F1.md`](../../squads/SQUAD-F1.md) — squad F1 atual
- [`docs/stories/sprint-plan-f1.md`](../../stories/sprint-plan-f1.md) — sprint plan F1 adiado pela decisão B.1
- [`CLAUDE.md`](../../../CLAUDE.md) — instruções mestras do projeto

---

_Design produzido via `superpowers:brainstorming` em sessão de 7 seções com aprovação incremental do usuário (seg 2026-04-20). Próximo artefato: plano executável via `superpowers:writing-plans`._
