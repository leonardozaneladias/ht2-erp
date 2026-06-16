---
title: 'ADR-RH-010: Atestado como entidade com workflow e a relação falta/atestado/afastamento'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-010: Atestado como entidade com workflow e a relação falta/atestado/afastamento

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** modelagem, rh, workflow, lgpd

> Pacote `ht2erp/modulo-rh`, aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Schema em [01 §C2/§C3/§C4/§4.2](../01-modelo-de-dominio.md); mecânica em [12](../12-ausencias-faltas-atestados-afastamentos.md). O **afastamento** permanece em [06](../06-linha-do-tempo.md); esta decisão acrescenta **atestado** e **falta/ocorrência** e a relação entre os três.

## Contexto e problema

O cliente pediu "controle de **faltas, atestados e afastamentos**". Na fundação, o afastamento já existe ([06 §5](../06-linha-do-tempo.md)) e o "atestado" aparecia apenas como **anexo** de um afastamento (`tipos_afastamento.exige_atestado` + `Anexo`). Isso cobre o atestado que **já virou** afastamento, mas **não** o ciclo real do dia a dia:

- um atestado **chega** por vários canais (colaborador via portal, gestor que recebeu por WhatsApp, RH);
- **espera análise** e é **aprovado ou rejeitado**;
- só então **abona** algo — horas de um dia, dias de falta, ou (se longo) **vira afastamento** INSS.

Além disso, **falta ≠ afastamento**: falta é um **fato pontual** (um dia/horas), afastamento é um **período** formalizado. Faltava modelar a **falta/ocorrência** e como o **atestado** a **abona**. A pergunta: **como modelar atestado e falta sem inchar o [06](../06-linha-do-tempo.md) nem reescrever o afastamento**, deixando claro o que é Fase 1 e o que é Fase 2?

## Drivers da decisão

- **Workflow real do atestado** — origem, status, quem analisou, o que abonou (auditável).
- **Distinguir os três conceitos** — falta (fato), atestado (documento), afastamento (período) — sem confundi-los numa tabela só.
- **Reuso do afastamento de [06](../06-linha-do-tempo.md)** — atestado longo **dispara** o afastamento existente, não o recria.
- **Self-service** — o colaborador **envia** atestado e acompanha; não se autoanalisa ([05 §9](../05-organograma-acl-hierarquica.md)).
- **LGPD** — `cid` é dado de saúde (art. 11), mesmo rigor do afastamento.
- **Fronteira de fase** — fundação na Fase 1; workflow/abono/INSS completos na Fase 2; apuração de folha na Fase 3; eSocial S-2230 na Fase 4.

## Alternativas consideradas

### Alt 1 — Atestado = só anexo de afastamento (a fundação)

- Prós: simples; já existe ([06 §5](../06-linha-do-tempo.md)).
- Contras: **sem ciclo** — não tem estados, origem, análise, nem abono de falta pontual; não atende "controle de atestados". **Mantida como fundação da Fase 1**, insuficiente para a Fase 2.

### Alt 2 — Tudo dentro de `funcionario_afastamentos` (campos extras de status/origem)

- Prós: uma tabela a menos.
- Contras: **mistura documento com período** e **falta com afastamento**; incha a tabela de afastamento com colunas de workflow que só fazem sentido para atestado; um atraso de 1h viraria "afastamento". Rejeitada.

### Alt 3 — Atestado como **entidade com máquina de estados** + `ocorrencias` para faltas (escolhida)

- Prós: cada conceito na sua tabela com sua semântica; workflow auditável; abono explícito (atestado→ocorrência/dias) ou geração de afastamento; reusa [06](../06-linha-do-tempo.md); self-service natural.
- Contras: mais tabelas/estados; exige conciliar atestado→afastamento; duas tabelas de "ausência" (atestado=documento, ocorrência=fato) a manter coerentes.

## Decisão

**Modelar atestado como entidade com workflow e introduzir `ocorrencias` para faltas** ([01 §C3/§C4](../01-modelo-de-dominio.md), [12](../12-ausencias-faltas-atestados-afastamentos.md)):

1. **`atestados`** (`[E][S][A][Anx]`) — máquina de estados `StatusAtestado` (`pendente → em_analise → aprovado | rejeitado`; um `aprovado` é desfeito por **`estornar`** → `estornado`, espelhando `horas_extras.cancelada`; `rejeitar` só de `pendente`/`em_analise`; **`rejeitado` e `estornado` são terminais absolutos** — correção = novo atestado), `OrigemAtestado` (canal de entrada), `dias_abonados`/`minutos_abonados`, `cid` (`encrypted`), `anexo_id`, `afastamento_id` nullable. A **matriz canônica de transições válidas/proibidas** está em [12 §2.4](../12-ausencias-faltas-atestados-afastamentos.md). Transições por Actions guardadas por permissão (padrão das horas extras — [07 §5](../07-jornada-horas-extras-folha.md)).
2. **`ocorrencias`** (`[E][S][A]`) — falta/atraso/saída (`TipoOcorrencia`), `justificada`/`abonada`, `atestado_id`/`tipo_afastamento_id`. Classificação **justificada/injustificada/abonada** derivada das flags.
3. **Aprovar um atestado** aplica **um** de três efeitos: abona **horas** de um dia (ocorrência abonada), abona **dias** (faltas justificadas/abonadas) ou — quando o `tipo_afastamento` tem **`suspende_contrato`** (INSS; o limiar de 15 dias é `config('rh.atestado.dias_limite_inss')` que **sugere**, não um `if` fixo) — **gera afastamento** chamando `RegistrarAfastamentoAction` de [06 §5.2](../06-linha-do-tempo.md) e gravando `afastamento_id`.
4. **O afastamento permanece em [06](../06-linha-do-tempo.md)** — tabela, Actions, conciliação de `status` e eventos `inicio/fim_afastamento` na linha do tempo **não** são reescritos; este escopo só os **dispara** e referencia.
5. **Fronteira de fase**:
    - **Fase 1 (fundação):** afastamento + anexo + flags ([06 §5](../06-linha-do-tempo.md)); as tabelas `atestados`/`ocorrencias` **existem** no schema (aditivas).
    - **Fase 2 (completo):** workflow do atestado, faltas/ocorrências com lançamento e classificação, abono, afastamento INSS com acompanhamento — tema "Gestão de ausências e tempo" de [09 §3](../09-roadmap-fases.md).
    - **Fase 3:** apuração de frequência/DSR/folha consome faltas/abonos ([07](../07-jornada-horas-extras-folha.md)).
    - **Fase 4:** eSocial **S-2230** ([09 §5](../09-roadmap-fases.md)).

## Consequências

**Positivas:**

- O **ciclo real** do atestado é modelado (origem → análise → aprovação → abono), auditável e com self-service.
- **Conceitos separados** (falta/atestado/afastamento) sem inchar nenhuma tabela; cada um com sua semântica.
- **Reuso** do afastamento de [06](../06-linha-do-tempo.md): atestado longo dispara `RegistrarAfastamentoAction`, sem duplicar lógica.
- **LGPD** consistente: `cid` com `encrypted` + `rh.atestados.ver_cid`, igual ao afastamento ([06 §5.3](../06-linha-do-tempo.md)).
- **Fronteira de fase explícita** — a Fase 1 entrega fundação aditiva; o workflow não vaza para o blueprint de B1–B7.

**Negativas / a gerenciar:**

- **Mais tabelas e estados** — `atestados` (workflow) + `ocorrencias`, além do afastamento existente.
- **Conciliação atestado → afastamento/ocorrência** precisa ser transacional e reversível (estorno desfaz o abono) — disciplina análoga à imutabilidade da timeline ([ADR-RH-005](ADR-RH-005-historico-eventos-imutaveis.md)).
- **Duas tabelas de "ausência"** (atestado = documento, ocorrência = fato) a manter coerentes; o vínculo `ocorrencias.atestado_id` é a ponte.
- **Apuração só na Fase 3** — na Fase 1/2 faltas/abonos são registrados e classificados, mas o efeito monetário (DSR, desconto) é da folha futura ([07](../07-jornada-horas-extras-folha.md)).

## Referências

- [12 — Ausências: Faltas, Atestados e Afastamentos](../12-ausencias-faltas-atestados-afastamentos.md) — modelo conceitual, workflow, abono, fronteiras.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§C2 afastamentos, §C3 atestados, §C4 ocorrências, §4.2 enums, §10 permissões).
- [06 — Linha do Tempo](../06-linha-do-tempo.md) (§5 afastamentos — permanece a fonte; §5.3 CID).
- [04 §5 — `tipos_afastamento`](../04-catalogos-configuraveis.md) — flags `remunerado`/`conta_como_falta`/`suspende_contrato`.
- [09 — Roadmap](../09-roadmap-fases.md) (§3 Fase 2, §4 Fase 3, §5 Fase 4).
- [ADR-RH-005: Histórico como eventos imutáveis](ADR-RH-005-historico-eventos-imutaveis.md) — disciplina de estorno/imutabilidade.
