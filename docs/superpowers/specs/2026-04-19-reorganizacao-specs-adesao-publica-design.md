---
title: Reorganização de SPECs + Adesão Pública via Código da Turma — Design
version: 1.0.0
date: 2026-04-19
status: draft
author: brainstorming session
---

# Design — Reorganização de SPECs + Adesão Pública via Código do Contrato

> Artefato do brainstorming de 2026-04-19. Captura decisões, alternativas descartadas e plano de execução. As especificações concretas vivem em `docs/SPEC-RESTRUCTURE-PLAN.md`, `docs/roadmap/BACKLOG_FUTURO.md`, `docs/features/foundation/*` e `docs/features/SPEC-010-*`.

> **[ATUALIZAÇÃO 2026-04-23 — inversão de modelo]** Após esclarecimento com o usuário, **o código humano-legível passou a pertencer ao Contrato** (não mais à Turma). `Contrato hasMany Turmas` (inversão — ver [SPEC-F-001 v0.3.0](../../features/foundation/SPEC-F-001-contrato-e-turma.md)). Uma turma = combinação concreta curso + ano + semestre dentro do contrato. Pacotes ganharam `categoria` (formatura/extra). Exemplo canônico muda de `MED-USP-2026` para `ARTFINAL-USP-MED-2026`. Spec ativa: [`SPEC-010 v2.0.0`](../../features/SPEC-010-adesao-publica-codigo-contrato.md). Plano executável: [`2026-04-19-adesao-publica-codigo-contrato-plan.md`](../plans/2026-04-19-adesao-publica-codigo-contrato-plan.md). As seções abaixo mantêm o raciocínio histórico do brainstorming original; onde houver conflito, o SPEC-010 v2 e F-001 v0.3 prevalecem.

---

## 1. Contexto e problema

### 1.1 Gatilho original

Usuário solicitou validação de um fluxo alternativo: "formando consegue aderir digitando o código da turma sem precisar fazer login; se já tiver conta, pode logar para pré-preencher dados". Esse fluxo **não existe** na documentação atual — wizard de adesão (SPEC-002) exige `auth:sanctum` em todas as etapas.

### 1.2 Diagnóstico expandido

Durante a brainstorm foram identificadas perdas sistemáticas do **PRD v3.1.0** (arquivado) para o **PRD v4** (ativo). A v4 virou um documento de **visão arquitetural** e perdeu a camada de **especificação funcional**. Conceitos governantes de negócio sumiram:

- **Contrato** como entidade comercial central (v4 só cita como conceito, não modela)
- Flags do contrato que governam UX: `exige_responsavel_cadastro`, `exige_responsavel_financeiro`, `permite_formando_resp_financeiro`, `permite_formando_resp_cadastro`
- **Dois responsáveis** distintos: cadastro ≠ financeiro
- **Multi-formando** (1 PortalUser ↔ N Formandos, para pais de gêmeos, pais cadastrando filhos)
- **Programações de valor** (preço variável por período)
- **Descontos e condições de pagamento** (regras de sobreposição)
- **Cálculo dinâmico de parcelas** (algoritmo completo)
- **Termos versionados** (snapshot imutável do termo aceito)
- **Reajustes contratuais** (índices IGPM/IPCA)
- **Gateway de pagamento** como infra reutilizável (existe em PLANEJAMENTO_BACKEND mas não como SPEC)

Os SPECs atuais (001–009) assumem o modelo evento-cêntrico da v4 e o 1:1 PortalUser↔Formando, o que **amplifica a divergência** a cada spec nova.

### 1.3 Escopo desta decisão

Três entregas encadeadas:

1. **Reorganização** documental — recuperar os conceitos perdidos via camada Foundation
2. **Poda** do v1 — remover do core inicial o que não é bloqueador de negócio (Enquetes, Seating)
3. **Spec nova** — Adesão pública via código da turma (SPEC-010), construída sobre a Foundation

---

## 2. Decisões arquiteturais

### 2.1 Modelo de código da turma

**Escolhido: A — Código único por Turma, compartilhado**

- Admin define manualmente (ex.: `MED-USP-2026`), sistema valida unicidade global
- Humano-legível, memorizável, distribuível por WhatsApp/email/cartaz
- Permanente enquanto `turma.adesao_publica_ativa = true`
- Regenerável: admin gera novo código = antigo vira inválido imediatamente

Alternativas descartadas:

- **B — Código por (Turma+Evento)**: Evento é mutável (turmas podem se juntar em um mesmo evento tardiamente), então o código por Evento quebraria quando turmas mergem
- **C — Híbrido com whitelist de CPFs**: adiciona fricção operacional; whitelist pode ser reintroduzida como SPEC futura se casos de abuso aparecerem

### 2.2 Identidade (CPF e PortalUser)

**Escolhido: A1 + B1**

- **A1**: se CPF já tem `PortalUser`, sistema **força login** para continuar (evita sequestro de identidade)
- **B1**: se CPF é novo, `PortalUser` é criado **no commit** da etapa 7 (não antes), com `status: incompleto` e email de ativação para definir senha

Alternativas descartadas:

- **A2/A3**: permitir adesão sem login para CPF existente → risco de fraude
- **B2**: criar `PortalUser` já na etapa 1 → pressão na tabela, lixo de abandonos
- **B3**: não criar conta no commit → obriga usuário a ativar antes de pagar; pior conversão

### 2.3 Resolução de Evento a partir do código

**Escolhido: A — Código da Turma lista eventos disponíveis**

- `turmas.codigo_acesso` identifica a Turma
- Sistema resolve Turma → Contrato ativo → Eventos abertos via `turma_evento`
- Se 1 evento → segue direto; se N → mostra lista; se 0 → erro
- Merge de turmas (várias turmas → mesmo evento) funciona sem tocar no código

Observação pós-decisão: durante a conversa, o usuário esclareceu que **Evento é vinculação tardia ao Contrato** (não ao código diretamente). Isso reforça a Opção A.

### 2.4 Entrada do código — URL ou formulário

**Escolhido: C — Ambos**

- URL compartilhável: `https://portalartfinal.com.br/adesao/MED-USP-2026` (QR code, link de WhatsApp)
- Formulário fallback: `/adesao` com input de código
- Proteções: `robots.txt` disallow + `<meta name="robots" content="noindex">` nas páginas públicas + rate limit agressivo por IP

### 2.5 Arquitetura do wizard público

**Escolhido: Abordagem 1 — Extensão mínima do SPEC-002**

- Middleware `ResolveAdesaoContext` despacha para `AutenticadoContext` (sanctum) ou `PublicoContext` (draft token JWT)
- Estado anônimo vive em **JWT assinado (HS256, TTL 48h)** transmitido no header `X-Adesao-Draft-Token` — sem tabela `adesoes_draft`
- No commit, transação atômica cria `PortalUser + Formando + Adesao + Parcelas`
- Auto-login token curto (15 min, uso único) evita perder o usuário pós-commit

Alternativas descartadas:

- **Abordagem 2 (wizard público espelho)**: duplicação de código, manutenção 2×
- **Abordagem 3 (PortalUser shadow)**: lixo de contas incompletas, pressão na tabela, risco de enumeração de CPFs

### 2.6 Modelo de domínio (correção v4)

Introduzir `Contrato` como entidade de primeira classe, alinhada ao PRD v3 (§4) e à menção conceitual do v4 (§2.2, §6.1):

```
Turma ── codigo_acesso ──> Contrato ── (vinculação tardia) ──> Evento
                             │                                   ▲
                             └─< Adesao                          │
                                    └─> pacotes                  │
                                    └─> produtos                 │
                                    └─> programações             │
                                                                 │
                 (outras Turmas) ─ Contratos ────────────────────┘
                                                     (N:1)
```

Cardinalidades:

- `Turma → Contrato` = 1:1 ativo por vez
- `Contrato → Evento` = 1:1 (nullable até vinculação tardia)
- `Evento → Contratos` = 1:N (uma festa para várias turmas)
- `Contrato → Adesões` = 1:N

### 2.7 Multi-formando e responsáveis

Recuperar da v3 o conceito `1 PortalUser → N Formandos` via pivô `portal_user_formandos`. Campos da `Adesao` separam:

- `portal_user_id` — conta que criou/paga (pode ser o próprio formando OU pai/responsável)
- `formando_id` — pessoa que vai formar
- `responsavel_*` — responsável financeiro capturado no wizard

Etapa 0 pós-validação do código: "Quem está fazendo esta adesão?" (eu sou o formando / estou cadastrando outra pessoa).

### 2.8 Poda de escopo v1

Escolhas confirmadas pelo usuário durante o brainstorm:

| Feature                            | Decisão                                                   | Destino                          |
| ---------------------------------- | --------------------------------------------------------- | -------------------------------- |
| **Enquetes**                       | ❌ Removida do v1                                         | `BACKLOG_FUTURO.md`              |
| **Seating / Mapa de mesas**        | ❌ Removida do v1 (Nível C — saída completa, não parcial) | `BACKLOG_FUTURO.md`              |
| **Reajustes por índice**           | ⚠️ Manter stub como F-008; possível corte futuro          | Foundation (com nota de revisão) |
| **RSVP**, **Extras**, **Convites** | ✅ Mantidos                                               | v1 core                          |

SP cortados do core: ~42 SP (enquetes ~8 + seating ~34).

---

## 3. Plano de execução (estratégia D — stubs primeiro)

### 3.1 Camada 1 — Foundation SPECs (11 specs)

Recuperam os conceitos perdidos da v3. Stubs são criados agora; expansão vem por sprint.

| #     | Spec                                 |    SP doc | Depende      |
| ----- | ------------------------------------ | --------: | ------------ |
| F-001 | Contrato e Turma                     |         5 | —            |
| F-002 | Responsáveis (cadastro + financeiro) |         3 | F-001        |
| F-003 | Multi-formando                       |         5 | F-002        |
| F-004 | Programações de valor                |         5 | F-001        |
| F-005 | Descontos e condições de pagamento   |         5 | F-004        |
| F-006 | Cálculo dinâmico de parcelas         |         8 | F-004, F-005 |
| F-007 | Termos versionados                   |         5 | F-001        |
| F-008 | Reajustes contratuais                |         3 | F-006        |
| F-009 | Gateway de pagamento (infra)         |         8 | —            |
| F-010 | Auth & Authorization base            |         3 | —            |
| F-011 | Auditoria append-only                |         2 | —            |
|       | **Total Foundation**                 | **52 SP** |              |

### 3.2 Camada 2 — Refactor dos SPECs existentes (7 refactors)

| Spec atual                    | Mudança principal                                                                                          |        SP |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------- | --------: |
| SPEC-001 login                | `/me` retorna `formandos[]` tipado; responsáveis                                                           |         2 |
| SPEC-002 wizard adesão        | `evento_ulid → contrato_ulid`; 2 responsáveis; programações; descontos; termo versionado; seletor formando |        13 |
| SPEC-003 financeiro/pagamento | Cálculo dinâmico; reajustes; depender de F-009                                                             |         5 |
| SPEC-004 convites/cotas       | `contrato.evento_id`                                                                                       |         2 |
| SPEC-005 RSVP público         | Context formando ativo                                                                                     |         2 |
| SPEC-007 extras               | Depender de F-009                                                                                          |         2 |
| SPEC-009 perfil               | Responsáveis separados; múltiplos formandos                                                                |         3 |
|                               | **Total refactor**                                                                                         | **29 SP** |

Arquivados: SPEC-006 seating, SPEC-008 enquetes.

### 3.3 Camada 3 — SPECs novos

| Spec         | Conteúdo                                          |        SP | Depende                           |
| ------------ | ------------------------------------------------- | --------: | --------------------------------- |
| **SPEC-010** | Adesão pública via código da turma                |        13 | F-001, F-002, F-003, F-009, F-010 |
| SPEC-011     | Admin: gestão de Contratos e Turmas               |         8 | F-001                             |
| SPEC-012     | Admin: gestão de Pacotes, Programações, Descontos |         8 | F-004, F-005                      |
| SPEC-013     | Admin: gestão de Termos                           |         5 | F-007                             |
| SPEC-014     | Admin: gestão de Formandos e Responsáveis         |         5 | F-003                             |
| SPEC-015     | E-mails transacionais                             |         5 | —                                 |
|              | **Total camada 3**                                | **44 SP** |

### 3.4 Ordem de execução

```
Agora (docs):
  1. SPEC-RESTRUCTURE-PLAN.md  ← umbrella visível
  2. BACKLOG_FUTURO.md          ← destino de enquetes + seating
  3. Mover SPEC-006 e SPEC-008 para _archive/future/
  4. Foundation stubs F-001 … F-011
  5. SPEC-010 completa

Depois (código, por fase):
  F1 (em curso): F-010 + F-011 (auth + auditoria) viram código primeiro
  F2: F-001 + F-002 + F-009 implementadas; SPEC-011 expandida
  F3: F-003 + F-004 + F-005 + F-006 + F-007; SPEC-001 + SPEC-002 refatoradas; SPEC-010 implementada
  F4: SPEC-004 refatorada; SPEC-015 implementada
  F6: SPEC-007 refatorada; F-008 reavaliada
  F7: hardening
```

---

## 4. Design detalhado de SPEC-010 (referência para a spec completa)

### 4.1 Data model

- `turmas.codigo_acesso` VARCHAR(32) único nullable + `turmas.adesao_publica_ativa` boolean
- `contratos` (tabela nova): ulid, turma_id, categoria, evento_id nullable, status, adesao_publica_ativa
- `adesoes`: remover `evento_id`; adicionar `contrato_id` NOT NULL, `portal_user_id` nullable, `draft_token_hash`, `origem_adesao` enum

### 4.2 Endpoints (público)

- `GET /api/v1/adesao/publico/{codigo}` — resolve código e retorna Contrato + pacotes
- `POST /api/v1/adesao/publico/{codigo}/iniciar` — emite draft_token ou retorna `409 MustLogin`
- `POST /api/v1/adesao/publico/simular` — simulação de parcelamento
- `POST /api/v1/adesao/publico/commit` — commit atômico com PortalUser + Formando + Adesão

### 4.3 Fluxo

Detalhado em `docs/features/SPEC-010-adesao-publica-codigo-contrato.md` seção 3 (Data Flow).

### 4.4 Segurança

- `robots.txt` + `noindex` nas páginas públicas
- Rate limit agressivo: 10/min GET {codigo}, 5/min iniciar, 3/min commit
- `draft_token` liga CPF (hash), jti para revogação via Redis
- Auto-login token de 15 min pós-commit, uso único, bound ao IP+UA
- Email de confirmação imediato com link "não fui eu" (cancelamento 1-clique em 72h)

### 4.5 Testes

Cobertura alvo: `CommitAdesaoPublicaAction` 100%, `DraftTokenService` 100%, `ResolveAdesaoContext` 100%, global ≥ 70%. Detalhes em SPEC-010 seção 6.

---

## 5. Riscos e mitigação

| Risco                                                                           | Probabilidade | Impacto | Mitigação                                                                                                      |
| ------------------------------------------------------------------------------- | :-----------: | :-----: | -------------------------------------------------------------------------------------------------------------- |
| Refactor SPEC-002 quebrar testes existentes                                     |     Alta      |  Médio  | Feature flag `contrato_model_enabled`; manter `evento_ulid` como deprecated alias por 1 sprint                 |
| Foundation SPECs consumirem tempo que F1 precisa para implementação             |     Média     |  Alto   | Estratégia D (stubs primeiro); expansão quando a sprint chegar                                                 |
| Cortes de seating/enquetes serem revertidos                                     |     Baixa     |  Médio  | Conteúdo preservado em `_archive/future/` + `BACKLOG_FUTURO.md`                                                |
| Usuário ativar código por engano (vazamento em grupo de WhatsApp errado)        |     Média     |  Baixo  | Admin pode regenerar instantaneamente; commitlint já força `admin` scope auditável                             |
| CPF enumeration via `/publico/iniciar` (atacante descobre quais CPFs têm conta) |     Média     |  Baixo  | Rate limit 5/min + retorno mascarado (`j***@gmail.com`) + alerta Sentry se >50 409s/h do mesmo IP              |
| Adesões órfãs (PortalUser criado, formando abandona)                            |     Alta      |  Baixo  | Job noturno cancela adesões `pendente_pagamento` > 7 dias; limpa PortalUser `incompleto` sem adesão em 30 dias |

---

## 6. Assunções explícitas

1. PRD v4 será **preservado** como documento de visão estratégica; as Foundation SPECs atuam como complemento funcional sem reescrever a v4.
2. Nenhum código de produção foi implementado ainda nos SPECs 001–009 (confirmado por revisão do branch `feature/planejamento-backend-api-v1`).
3. O `docs/modules/` tem 19 placeholders ("A definir"); serão expandidos por sprint, puxando conteúdo das Foundation SPECs.
4. O JWT do draft_token usa segredo dedicado (`DRAFT_TOKEN_SECRET`) — não o `APP_KEY`.
5. O auto-login pós-commit não substitui o fluxo normal de login com senha — a pessoa ainda precisa definir senha via email para próximos acessos.
6. Multi-formando é **essencial** para v1 (cenário "pais de gêmeos" é negócio real); não pode ser adiado.
7. Seating saiu do v1 sem substituto no portal — formandos/convidados **não verão** informação de mesa no portal no MVP.

---

## 7. Perguntas pendentes (a resolver durante implementação)

1. **OQ-1** — Formato exato do código: livre (ex: "MED-USP-2026") ou padronizado pelo sistema (ex: "MEDUSP-A3F7")? _Proposto:_ livre, mas com regex validação `^[A-Z0-9-]{4,32}$`.
2. **OQ-2** — Regeneração do código invalida adesões em curso? _Proposto:_ não — JWT draft_token carrega `turma_ulid` direto, não o código. Regeneração só afeta NOVAS adesões após a troca.
3. **OQ-3** — Auto-login token pós-commit expira sessão normal quando pessoa loga depois com senha? _Proposto:_ sim — Sanctum revoga tokens anteriores por padrão no `Auth::login()`.
4. **OQ-4** — Formando menor de idade (< 18): o responsável precisa ser obrigatório? Depende das flags do Contrato (F-001). _Proposto:_ sim, validado via `data_nascimento` e flags `exige_responsavel_*` do contrato.
5. **OQ-5** — Pessoa que digita código em turma errada (ex: quer MED-USP mas digitou ODONTO-USP) — alerta antes do commit? _Proposto:_ Etapa 6 (Revisão) mostra "Você está se inscrevendo na turma X — confirme" como checkpoint.

---

## 8. Referências

### 8.1 Documentos fonte consultados

- PRD v3.1.0 (arquivado, §4, §11, §14) — conceitos recuperados
- PRD v4 ativo (§1, §2, §3.1–3.2, §5.1–5.4, §6.1–6.4, §11) — base arquitetural
- SPEC-001 a SPEC-009 — base dos refactors da Camada 2
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2, §8, §6 — arquitetura HTTP/gateway/auth
- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §5, §6, §8 — rotas e state
- `docs/prd/ROADMAP.md` — fases e SP

### 8.2 Artefatos gerados a partir deste design

- `docs/SPEC-RESTRUCTURE-PLAN.md` — umbrella visível
- `docs/roadmap/BACKLOG_FUTURO.md` — features deferidas
- `docs/features/foundation/SPEC-F-001..F-011` — stubs Foundation
- `docs/features/SPEC-010-adesao-publica-codigo-contrato.md` — spec completa
- `docs/_archive/future/SPEC-006-mapa-mesas-seating.md` (movido)
- `docs/_archive/future/SPEC-008-enquetes.md` (movido)

---

_Fim do design. Próximo artefato a ser gerado: `SPEC-RESTRUCTURE-PLAN.md` e `BACKLOG_FUTURO.md`._
