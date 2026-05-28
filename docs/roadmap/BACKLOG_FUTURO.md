---
title: Backlog de Funcionalidades Futuras (v2+)
version: 1.0.0
date: 2026-04-19
status: tracking
---

# Backlog de Funcionalidades Futuras (v2+)

> Registro das funcionalidades removidas do escopo do v1 e candidatas a versões futuras. Não é um roadmap comprometido — é um **depósito de decisões**. Cada item aqui tem um motivo de deferral e um gatilho para re-priorização.
> Fonte original das decisões: [design doc 2026-04-19](../superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md) e [SPEC-RESTRUCTURE-PLAN](../SPEC-RESTRUCTURE-PLAN.md).

---

## Template por item

```markdown
## NN — <Nome>

**Removida do:** v1 (decisão YYYY-MM-DD)
**Proposta por:** <doc/seção original>
**Motivo do deferral:** <justificativa>
**SP estimado na época:** <n>
**Depende de:** <pré-requisitos para implementação futura>
**Trigger para re-priorização:** <condição objetiva que deve ativar revisão>
**Cross-refs removidos no v1:** <lista de docs atualizados>
**Conteúdo preservado em:** <caminho do arquivo arquivado, se houver>
```

---

## 01 — Enquetes e votações

**Removida do:** v1 (decisão 2026-04-19)
**Proposta por:** PRD v4 §3.5 (Bounded Context Engajamento), §5.1 ER, §5.4 atributos, §6.6 regras, §6.9 capacidades
**Motivo do deferral:** MVP prioriza fluxo comercial (adesão → pagamento) e operacional básico (convites + RSVP). Enquetes são **engajamento nice-to-have** — não bloqueiam a condução de uma formatura real. Nenhuma organizadora citou como requisito contratual.
**SP estimado na época:** ~8 SP (SPEC-008)
**Depende de:** v1 estável em produção; adesão + pagamento + convites operacionais
**Trigger para re-priorização:**

- ≥ 3 organizadoras solicitarem como gap funcional
- OU comissão de formandos pedir como ferramenta de decisão coletiva recorrente
- OU partner/investidor requisitar como diferencial de produto

**Cross-refs a atualizar no v1:**

- `docs/prd/PRD_v4.md` §1.4 (mover de "core" para "fora do core")
- `docs/prd/PRD_v4.md` §3.5, §5.1, §5.4, §6.6, §6.9 (remover ou marcar `deferred-v2`)
- `docs/prd/ROADMAP.md` F6 (renomear sem "enquetes")
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` (remover rotas, controllers, migrations, actions de enquetes)
- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` (remover `routes/enquetes`, `use-enquetes.ts`, `EnquetesPage`)
- `docs/features/README.md` (remover entrada SPEC-008)

**Conteúdo preservado em:** [`docs/_archive/future/SPEC-008-enquetes.md`](../_archive/future/SPEC-008-enquetes.md)

---

## 02 — Seating / Mapa de Mesas (completo)

**Removida do:** v1 (decisão 2026-04-19, Nível C — saída completa)
**Proposta por:** PRD v4 §3.4 (Bounded Context Seating), §5.1 ER (MapaMesa, Setor, Mesa, Assento, ReservaAssento), §5.4, §6.4 concorrência, §6.9 capacidades, §6.10 Fluxo 3
**Motivo do deferral:** Organizadoras brasileiras hoje organizam mesas fora do sistema (Excel, WhatsApp). A complexidade técnica do módulo (holds com Redis lock, idempotência, janela temporal, drag-drop UI, reservas concorrentes) é alta (~34 SP) e entrega valor marginal no MVP. No v1, formandos/convidados **não veem informação de mesa** no portal — a comissão divulga por canal próprio.
**SP estimado na época:** ~34 SP (SPEC-006 + fase F5 inteira)
**Depende de:**

- Adesão + convites + RSVP operacionais e com adoção real
- Decisão de produto: voltar em **Nível B** (seating simples: admin atribui, portal mostra read-only) ou direto em **Nível A** (mapa interativo completo)?

**Trigger para re-priorização:**

- ≥ 3 organizadoras solicitarem visualização de mesa no portal do formando
- OU comissão pedir interface de atribuição que substitua o Excel atual
- OU cliente enterprise exigir como critério de aquisição

**Cross-refs a atualizar no v1:**

- `docs/prd/PRD_v4.md` §1.4 (mover seating para "fora do core")
- `docs/prd/PRD_v4.md` §3.4, §5.1, §5.4 (ReservaAssento), §6.4, §6.9 (seating), §6.10 Fluxo 3 (remover)
- `docs/prd/ROADMAP.md` F5 (deletar fase inteira; renumerar F6→F5, F7→F6, F8→F7)
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` (remover migrations mapas_mesas, setores, mesas, assentos, reservas_assentos, reservas_historico + routes `/seating/*` + controllers + actions + policies)
- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` (remover `routes/portal/mesas.tsx`, `hold-store.ts`, componentes de seating, hook `use-seating.ts`)
- `docs/features/README.md` (remover entrada SPEC-006)
- Dependências em outros SPECs (SPEC-004 convites pode referenciar reserva_id — tornar nullable/ignorável)

**Conteúdo preservado em:** [`docs/_archive/future/SPEC-006-mapa-mesas-seating.md`](../_archive/future/SPEC-006-mapa-mesas-seating.md)

---

## Candidatos em avaliação (não confirmados)

Itens discutidos mas mantidos no v1 por ora. Registrados aqui para rastreabilidade caso sejam cortados depois.

### 03 — Reajustes por índice (IGPM/IPCA)

**Status atual:** Mantido como Foundation `SPEC-F-008 Reajustes contratuais` (stub, 3 SP)
**Risco de corte futuro:** MÉDIO — pouco comum em formaturas curtas (1 ano). Pode ser deferido se ficar claro que a maioria dos contratos tem duração < 12 meses ou preço fixo pré-negociado.
**Se cortado:** preço da parcela vira fixo após o commit; cálculo é linear por `valor_total / qtd_parcelas`.

### 04 — Extras (venda de produtos pós-adesão)

**Status atual:** Mantido — SPEC-007 (existe e será refatorado para consumir F-009)
**Motivo para manter:** Fonte de receita recorrente para organizadoras (convites extras, kits, upgrades). Cortar pode reduzir ticket médio em até 30%.

### 05 — Check-in presencial por QR Code

**Status atual:** Já listado em PRD v4 §1.4 "Fora do core inicial"
**Observação:** não requer migração para este arquivo pois v4 já contempla.

### 06 — Marketplace de fornecedores terceiros

**Status atual:** Já listado em PRD v4 §1.4 "Fora do core inicial"

### 07 — Networking social entre convidados

**Status atual:** Já listado em PRD v4 §1.4 "Fora do core inicial"

### 08 — BI analítico avançado

**Status atual:** Já listado em PRD v4 §1.4 "Fora do core inicial"

---

## Manutenção

- Este documento é **append-only** — não remover itens; apenas atualizar status
- Quando um item for re-priorizado (promovido para versão próxima), marcar `status: promoted-to-vN` sem apagar o histórico
- Revisar trimestralmente: se um trigger foi atingido mas ninguém priorizou, avaliar se o trigger precisa ser recalibrado
- Novas remoções de escopo devem ser adicionadas aqui antes de mexer em qualquer outro doc
