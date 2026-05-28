---
title: SPEC-F-004 — Programações de Valor
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-004
fase: foundation
story_points: 5
depends_on: [SPEC-F-001]
unlocks: [SPEC-F-005, SPEC-F-006, SPEC-002, SPEC-012]
---

# SPEC-F-004 — Programações de Valor

> **Fundacional.** Recupera do PRD v3.1.0 §6 o conceito de "preço variável por período" — adesão antecipada custa diferente de adesão de última hora. Sem isso, pacotes têm preço fixo e o negócio perde a principal alavanca de conversão (early bird).

---

## 1. Conceito

Um pacote (ou produto) pode ter **múltiplas programações de valor** vigentes em períodos diferentes. A programação ativa no momento da adesão define o preço aplicado (imutável via snapshot).

### 1.1 Exemplos

| Período         |     Preço | Tipo        |
| --------------- | --------: | ----------- |
| 01/jan → 30/jun | R$ 10.000 | Early bird  |
| 01/jul → 30/set | R$ 12.000 | Normal      |
| 01/out → 31/dez | R$ 14.000 | Última hora |

### 1.2 Tipos de programação

- **Absoluta**: preço fixo no período
- **Desconto**: percentual sobre o preço base (ex: -15% em junho) — pode compor com SPEC-F-005

---

## 2. Modelo de dados (preview)

### 2.1 `produto_programacoes` — nova tabela

| Campo                       | Tipo                                                 |
| --------------------------- | ---------------------------------------------------- |
| `id`                        | BIGINT PK                                            |
| `ulid`                      | CHAR(26) UNIQUE                                      |
| `produto_id` OR `pacote_id` | FK (polymorphic)                                     |
| `tipo`                      | `absoluta` / `desconto_percentual` / `desconto_fixo` |
| `valor_centavos`            | INTEGER (se absoluta ou desconto fixo)               |
| `percentual`                | DECIMAL(5,2) (se desconto percentual)                |
| `data_inicio`               | DATETIME                                             |
| `data_fim`                  | DATETIME                                             |
| `prioridade`                | SMALLINT (resolução de sobreposição)                 |
| `ativa`                     | BOOLEAN                                              |
| `timestamps`                |                                                      |

### 2.2 Regra de sobreposição

Se mais de uma programação estiver ativa no momento:

- Absoluta prevalece sobre desconto
- Entre absolutas, maior `prioridade` vence
- Entre descontos, somar? Multiplicar? **a decidir na expansão** (proposta: somar percentuais, capar em 50%)

---

## 3. Resolução em tempo de adesão

```
preco_aplicado(pacote, data) =
    programacao = pacote.programacoes
                       .filter(ativa = true, data_inicio <= data <= data_fim)
                       .orderByDesc(prioridade)
                       .first()
    if programacao.tipo = absoluta:
        return programacao.valor_centavos
    if programacao.tipo = desconto_percentual:
        return pacote.preco_base_centavos * (1 - programacao.percentual/100)
    if programacao.tipo = desconto_fixo:
        return max(0, pacote.preco_base_centavos - programacao.valor_centavos)
    return pacote.preco_base_centavos  // fallback
```

Snapshot em `pacote_snapshot` da adesão preserva:

- `preco_aplicado_centavos`
- `programacao_ulid` (que programação foi usada)
- `preco_base_centavos` (para auditoria)

---

## 4. Pontos a expandir na versão `draft`

- [ ] Decisão: tabela polimórfica ou separar em `pacote_programacoes` + `produto_programacoes`?
- [ ] Validação: programações do mesmo pacote não podem ser absolutas e sobrepor no tempo
- [ ] Action `ResolverProgramacaoVigenteAction(Pacote, Carbon $data): Programacao`
- [ ] Interface admin (SPEC-012): tela de edição com calendário visual
- [ ] Testes: sobreposição absoluta+absoluta, absoluta+desconto, desconto+desconto, transição na meia-noite (timezone)
- [ ] Endpoint `GET /api/v1/contratos/{ulid}/pacotes?at=2026-04-19` mostra preço vigente em data específica

---

## 5. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §6 — conceito original com tabela `produto_programacoes` e exemplos
- [`SPEC-F-005`](SPEC-F-005-descontos-condicoes.md) — complementa (descontos adicionais por método de pagamento)
- [`SPEC-F-006`](SPEC-F-006-calculo-parcelas.md) — consome (cálculo parte do preço resolvido aqui)

---

_**Estado:** `stub`._
