---
title: SPEC-F-006 — Cálculo Dinâmico de Parcelas
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-006
fase: foundation
story_points: 8
depends_on: [SPEC-F-004, SPEC-F-005]
unlocks: [SPEC-F-008, SPEC-002, SPEC-003]
---

# SPEC-F-006 — Cálculo Dinâmico de Parcelas

> **Fundacional.** Recupera do PRD v3.1.0 §9 o algoritmo completo de cálculo de parcelas. Hoje SPEC-002 tem `intdiv` simples — esta spec formaliza arredondamento, ajuste na última parcela, alinhamento de vencimentos e interação com SPEC-F-004 (programações) e SPEC-F-005 (descontos).

---

## 1. Inputs do cálculo

| Input                              | Origem                                  |
| ---------------------------------- | --------------------------------------- |
| `preco_base_centavos`              | Pacote/produto                          |
| `programacao_vigente`              | SPEC-F-004 resolve no momento da adesão |
| `desconto_aplicavel`               | SPEC-F-005 (método, volume, cupom)      |
| `qtd_parcelas`                     | Escolha do formando (etapa 4 do wizard) |
| `metodo_primeira`, `metodo_demais` | Escolha do formando                     |
| `data_vencimento_dia`              | Escolha do formando (1,5,10,15,20,25)   |
| `contrato.condicoes_pagamento`     | Constraints disponíveis                 |

---

## 2. Algoritmo (alto nível)

```
1. valor_total = aplicar_descontos(
      preco_programacao(pacote, hoje),
      condicoes(metodo, qtd_parcelas, cupom)
   )

2. valor_parcela_base = valor_total / qtd_parcelas      // divisão em centavos
3. resto = valor_total - (valor_parcela_base * qtd_parcelas)
4. primeira_parcela = valor_parcela_base + resto        // absorve centavos extras

5. para cada parcela:
      vencimento = primeiro_dia_util(mes_atual + i, dia_escolhido)
      metodo = (i == 1) ? metodo_primeira : metodo_demais
      valor = (i == 1) ? primeira_parcela : valor_parcela_base

6. snapshot em `adesoes.calculo_snapshot_json`:
      { preco_base, programacao_ulid, descontos_aplicados, condicao_ulid,
        total_bruto, total_liquido, parcelas[]: {numero,vencimento,valor,metodo} }
```

---

## 3. Regras operacionais

- **Centavos sempre INTEGER** (nunca float) — ver CLAUDE.md §7.3
- **Primeira parcela absorve resto** — preferido sobre "rateio proporcional" por ser mais previsível
- **Vencimento em dia útil**: se o dia escolhido cair em sábado/domingo/feriado, antecipa para próximo dia útil (ou posterga, conforme regra do contrato — a decidir)
- **Feriados**: consulta à base de feriados nacionais + feriado local da cidade do evento (se disponível)
- **TZ**: tudo em `America/Sao_Paulo` no MVP

---

## 4. Simulação (endpoint)

`POST /api/v1/adesoes/simular` (já existe em SPEC-002, será refatorado para consumir esta spec):

```json
{
    "contrato_ulid": "01J...",
    "pacote_ulid": "01J...",
    "qtd_parcelas": 10,
    "metodo_primeira_parcela": "pix",
    "metodo_demais": "boleto",
    "data_vencimento_dia": 5,
    "cupom": "EARLY2026"
}
```

Response inclui breakdown completo:

```json
{
    "data": {
        "preco_base_centavos": 1500000,
        "programacao_aplicada": { "ulid": "01J...", "nome": "Early bird Q1" },
        "descontos_aplicados": [
            { "ulid": "01J...", "nome": "Cupom EARLY2026", "percentual": 10 },
            { "ulid": "01J...", "nome": "À vista PIX", "percentual": 5 }
        ],
        "total_bruto_centavos": 1500000,
        "total_liquido_centavos": 1282500,
        "valor_parcela_base_centavos": 128250,
        "valor_primeira_centavos": 128250,
        "parcelas": [
            { "numero": 1, "valor_centavos": 128250, "metodo": "pix", "vencimento": "2026-05-05" },
            { "numero": 2, "valor_centavos": 128250, "metodo": "boleto", "vencimento": "2026-06-05" }
        ]
    }
}
```

---

## 5. Pontos a expandir na versão `draft`

- [ ] Decisão: dia não-útil antecipa ou posterga? (proposta: posterga, default comercial brasileiro)
- [ ] Calendário de feriados: pacote `checkdigit/feriados-brasileiros` ou API externa?
- [ ] Tratar `qtd_parcelas = 1` como caso especial (vencimento = hoje + 3 dias úteis?)
- [ ] Action `CalcularPlanoParcelasAction(Contrato, Pacote, DadosPlano): PlanoCalculadoDTO`
- [ ] DTOs: `PlanoCalculadoDTO`, `ParcelaCalculadaDTO`, `DescontoAplicadoDTO`
- [ ] Testes: cap de 50% de desconto; parcela única; 12 parcelas; data no feriado; cupom inválido
- [ ] Integração com SPEC-F-008 (reajustes): se contrato tem reajuste ativo, parcelas futuras podem ser recalculadas

---

## 6. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §9 — algoritmo original
- [`SPEC-F-004`](SPEC-F-004-programacoes-valor.md), [`SPEC-F-005`](SPEC-F-005-descontos-condicoes.md) — entradas
- [`SPEC-F-008`](SPEC-F-008-reajustes.md) — pode re-alterar parcelas em curso
- [`SPEC-003`](../SPEC-003-financeiro-pagamento.md) — consumidor (extrato financeiro)

---

_**Estado:** `stub`. É a spec com maior complexidade algorítmica da Foundation._
