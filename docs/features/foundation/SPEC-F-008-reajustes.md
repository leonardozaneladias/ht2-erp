---
title: SPEC-F-008 — Reajustes Contratuais (por índice)
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-008
fase: foundation
story_points: 3
depends_on: [SPEC-F-006]
unlocks: [SPEC-003]
---

# SPEC-F-008 — Reajustes Contratuais (por índice)

> **Fundacional — avaliar corte.** Recupera do PRD v3.1.0 §14.16 o sistema de reajustes de parcelas futuras por índice (IGPM, IPCA, INPC). Candidato a ser movido para [BACKLOG_FUTURO](../../roadmap/BACKLOG_FUTURO.md) se uso real em formaturas for raro.

---

## 1. Quando aplicar

Contratos longos (>12 meses de parcelas) podem prever reajuste anual. O índice é escolhido pelo admin ao criar o Contrato. Formaturas típicas (~10 meses) raramente precisam — por isso esta spec é **stub com avaliação de corte**.

---

## 2. Modelo de dados (preview)

### 2.1 `indices_reajuste` — nova tabela

| Campo              | Tipo                              |
| ------------------ | --------------------------------- |
| `id`, `ulid`       |                                   |
| `codigo`           | enum: `IGPM`, `IPCA`, `INPC`      |
| `mes_referencia`   | DATE (YYYY-MM-01)                 |
| `valor_percentual` | DECIMAL(7,4) — acumulado 12 meses |
| `fonte`            | VARCHAR(200) (URL ou descrição)   |
| `imported_at`      | DATETIME                          |

### 2.2 `contratos` — campos adicionados

| Campo                      | Tipo                     |
| -------------------------- | ------------------------ |
| `indice_reajuste_codigo`   | VARCHAR(10) nullable     |
| `reajuste_aniversario_mes` | SMALLINT (1-12) nullable |

### 2.3 `parcelas` — campos adicionados

| Campo                       | Tipo                   |
| --------------------------- | ---------------------- |
| `valor_original_centavos`   | INTEGER (pré-reajuste) |
| `valor_reajustado_centavos` | INTEGER nullable       |
| `reajuste_aplicado_em`      | DATETIME nullable      |
| `indice_aplicado_ulid`      | FK nullable            |

---

## 3. Fluxo

### 3.1 Job mensal

- `AplicarReajusteAnualJob` roda dia 1 de cada mês
- Para cada Contrato com reajuste ativo e aniversário no mês: busca último índice publicado
- Calcula fator e aplica em parcelas futuras (status `pendente`, vencimento > hoje)
- Notifica formandos via email sobre o reajuste (valor antigo → novo)

### 3.2 Importação de índice (manual pelo admin)

- Admin insere novo valor mensal no painel
- Sistema valida: não sobrescreve índices passados; só aceita índices futuros ou mês corrente

---

## 4. Decisão pendente — manter ou cortar?

| Argumento                                | Manter | Cortar |
| ---------------------------------------- | ------ | ------ |
| Formaturas típicas <12 meses             | ❌     | ✅     |
| Algumas organizadoras contratam 24 meses | ✅     | ❌     |
| Complexidade de job + emails             | ❌     | ✅     |
| Custo de compliance (preços variáveis)   | ❌     | ✅     |

**Ação:** revisar na primeira semana de F2. Se ≥ 80% dos contratos em operação tiverem < 12 meses, mover para `BACKLOG_FUTURO.md`.

---

## 5. Pontos a expandir na versão `draft`

- [ ] Validação: reajuste só aplica em parcelas `pendente` futuras (nunca em `pago` ou `vencido`)
- [ ] Opção de ignorar reajuste (formando aceita e paga valor original) — feature avançada
- [ ] Testes: reajuste > 10% (alerta), reajuste negativo (cap em 0%)
- [ ] UI admin: tela de edição de índices mensais + preview de impacto no contrato

---

## 6. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §14.16 — Gestão de Índices de Reajuste (tela admin)
- [`SPEC-F-006`](SPEC-F-006-calculo-parcelas.md) — interage (recalcula parcelas futuras)

---

_**Estado:** `stub`. Candidato a corte — revisar antes de F2._
