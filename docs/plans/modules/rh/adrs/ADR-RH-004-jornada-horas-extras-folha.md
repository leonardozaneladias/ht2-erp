---
title: 'ADR-RH-004: Jornada, horas extras e fundação de folha'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-004: Jornada, horas extras e fundação de folha

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** calculo, folha, rh

> Pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Schema canônico em [01 — Modelo de Domínio](../01-modelo-de-dominio.md); fórmula e workflow em [07 — Jornada, Horas Extras e Folha](../07-jornada-horas-extras-folha.md).

## Contexto e problema

O módulo precisa modelar **jornada** (escalas), **horas extras** (lançamento, cálculo, aprovação) e a **fundação de folha**, com três exigências de exatidão e auditoria:

1. **Precisão numérica.** HE envolve duração (minutos), valor-hora (dinheiro) e fatores percentuais (50%, 100%, adicional noturno). Representar qualquer um em `float` reintroduz o erro de arredondamento que [ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md) proíbe — em centavos disputáveis e em horas fracionadas.
2. **Imutabilidade do cálculo.** Uma HE aprovada foi calculada sob um salário/escala/fator **daquele momento**. Se o salário mudar amanhã, recalcular a HE antiga adulteraria um valor já aprovado (e potencialmente pago) — disputa trabalhista e perda de auditoria, exatamente o que [ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md) resolve.
3. **Flexibilidade controlada do fator.** O fator de HE tem valor-padrão por tipo (regra de negócio → enum), mas convenções coletivas permitem que uma empresa use um percentual diferente. Precisa de type-safety **e** de override por empresa, sem virar string mágica nem perder o CHECK.

E uma **fronteira de escopo**: a Fase 1 entrega a _fundação_ de folha, não a folha. Apurar holerite/eSocial agora explodiria o escopo.

## Drivers da decisão

- Exatidão total ([ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md)): nenhum centavo nem minuto errado — inteiros em todo o cálculo.
- Imutabilidade do que foi aprovado ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)): a HE aprovada é auto-contida e não muda quando os mestres mudam.
- Fator de HE com type-safety ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)) **e** customização por empresa.
- Workflow de aprovação confiável (máquina de estados) seguindo a cadeia do organograma ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)).
- Escopo Fase 1 contido: fundação (rubricas + tabelas legais + ponte HE→rubrica), sem apuração.

## Alternativas consideradas

### Alt 1: Horas e dinheiro em `float`

- Prós: aritmética "natural" sem helper.
- Contras: erro de arredondamento documentado há décadas (`0.1 + 0.2 !== 0.3`); soma de HE não bate com o total; **proibido** por [ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md) (e CLAUDE.md §19). Rejeitada.

### Alt 2: Recalcular a HE sempre (sem snapshot)

- Prós: zero duplicação; "valor sempre derivado do estado atual".
- Contras: o salário/escala muda — recalcular adultera HE **já aprovada/paga**. Quebra a invariante de imutabilidade ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)) e gera disputa. Rejeitada.

### Alt 3: Fator de HE só no banco (coluna/linha editável)

- Prós: 100% configurável por empresa.
- Contras: perde CHECK e `match` exaustivo; o código passa a interpretar string mágica para saber se é noturno/DSR; "regra em dado". Contra [ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md). Rejeitada.

### Alt 4: Fator de HE só no enum (sem override)

- Prós: máxima type-safety; CHECK; lógica no enum.
- Contras: **engessa o cliente** — convenção coletiva com percentual diferente exigiria deploy. Insuficiente para a realidade trabalhista.

### Alt 5: Inteiros + snapshot imutável + enum com override (escolhida)

- Prós: exatidão ([ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md)), imutabilidade ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)) e type-safety com flexibilidade ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)) ao mesmo tempo; default no enum (com CHECK), override consciente por empresa.
- Contras: exige helpers de conversão na UI; o conceito "fator em basis points" e "override por empresa" precisa ser entendido; mais campos de snapshot na tabela.

## Decisão

**Tipos numéricos — nada de float:**

- **Durações** em **minutos inteiros** (`*_minutos`): `horas_extras.minutos` (CHECK `> 0`), `escalas.carga_semanal_minutos`. Horários do dia em `TIME`.
- **Dinheiro** em **centavos** `INTEGER` ([ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md)): `salario_base_centavos`, `valor_hora_base_centavos`, `valor_calculado_centavos`.
- **Fatores** em **basis points inteiros** (`*_bps`): `percentual_aplicado_bps` (ex.: 50% = 5000 bps, 100% = 10000 bps) — sem percentual fracionado em float.

**Valor-hora** deriva do salário pelo **divisor configurável** da escala (`escalas.horas_mensais_divisor`, default **220**) e do regime (`RegimeTrabalho::baseCalculoHoraExtra()`). O cálculo é inteiro de ponta a ponta.

**Fator de HE** mora no enum `TipoHoraExtra` (`fatorPadraoBps(): int`, `adicionalNoturno(): bool`) — type-safety + CHECK — **com override opcional por empresa** (convenção coletiva). O override é o **catálogo tenant fino `fator_horas_extras`**, agora **formalizado na fonte de verdade** ([01 §A10](../01-modelo-de-dominio.md); resolução de precedência `override ativo ?? fatorPadraoBps()` em [07 §3.3](../07-jornada-horas-extras-folha.md)) — não mais só uma promessa do ADR. O default vem do enum; o override, quando existe, é aplicado e **registrado no snapshot**.

**Snapshot de cálculo imutável** ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)): na **aprovação**, a HE congela `percentual_aplicado_bps`, `valor_hora_base_centavos`, `valor_calculado_centavos` e a memória de cálculo em `snapshot_calculo` (JSONB). Depois disso, **mudar o salário/escala/fator não altera** a HE aprovada — escrita uma vez, lida muitas; nunca em `WHERE` operacional.

**Workflow como máquina de estados** via Actions: `StatusHoraExtra` (`rascunho → lancada → (aprovada | rejeitada) → paga`, e `cancelada`), com `isFinal()` marcando estados terminais. `horas_extras` **não** tem `deleted_at` — cancelamento é status. A aprovação é restrita ao gestor na cadeia do organograma ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)) + permissão `rh.horas_extras.aprovar`.

**Fundação de folha (não folha):** `rubricas` (catálogo tenant, `natureza` + incidências `incide_inss/fgts/irrf`) + `tabelas_legais` (referência global por vigência: INSS/IRRF/salário-família, payload JSONB de faixas). A **HE aprovada vira rubrica** via `horas_extras.rubrica_id` / `rubricas.referencia_he_tipo` (ponte). **Fronteira explícita:** **sem apuração, sem holerite, sem eSocial** na Fase 1 — só modelagem, seed e ligação. Detalhe em [07 §Folha](../07-jornada-horas-extras-folha.md).

## Consequências

**Positivas:**

- Exatidão garantida: minutos/centavos/bps inteiros; soma de HE bate com o total; testes determinísticos ([ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md)).
- HE aprovada é auto-contida e à prova de mudança futura nos mestres ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)) — disputa resolvida pelo `snapshot_calculo`.
- Fator com type-safety **e** flexibilidade: default no enum (CHECK), override por empresa para convenção coletiva, ambos rastreáveis no snapshot.
- Workflow auditável (máquina de estados + cadeia de aprovação); fundação de folha pronta para a fase de apuração sem reescrever a base.

**Negativas / a gerenciar:**

- A UI precisa converter minutos→horas e centavos→R$ e bps→% na apresentação (helpers centralizados; arch test contra `float` em models/DTOs).
- `snapshot_calculo` duplica dados (base/hora, fator) — aceito: é o ponto da imutabilidade; JSONB cresce o row (TOAST compacta).
- Override de fator por empresa é mais um caminho de configuração — deve ser explícito e logado; sem override, vale o default do enum.
- Recalcular HE **não aprovada** é livre; recalcular **aprovada** é proibido — a Action deve barrar (não confiar só na convenção).
- Fronteira de escopo precisa ser respeitada: a tentação de "só apurar rapidinho" reabre eSocial/holerite — fora da Fase 1.

## Referências

- [ADR-0014: Valores monetários em INTEGER centavos](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md) — dinheiro/duração/fator inteiros.
- [ADR-0009: Snapshots JSONB imutáveis](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md) — snapshot de cálculo congelado na aprovação.
- [ADR-0010: Enums PHP backed](../../../../architecture/adrs/ADR-0010-enums-php-backed.md) — `TipoHoraExtra`/`StatusHoraExtra` (fator + máquina de estados).
- [ADR-RH-003: ACL hierárquica por organograma](ADR-RH-003-acl-hierarquica-organograma.md) — aprovação segue a cadeia do organograma.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§3 A6–A9 escalas/rubricas, **§A10 `fator_horas_extras`**, §3 D1 `horas_extras`, §4 enums, §10 permissões) · [07 — Jornada, Horas Extras e Folha](../07-jornada-horas-extras-folha.md).
