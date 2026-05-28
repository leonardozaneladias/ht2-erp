---
title: SPEC-F-005 — Descontos e Condições de Pagamento
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-005
fase: foundation
story_points: 5
depends_on: [SPEC-F-004]
unlocks: [SPEC-F-006, SPEC-002, SPEC-012]
---

# SPEC-F-005 — Descontos e Condições de Pagamento

> **Fundacional.** Recupera do PRD v3.1.0 §7 o sistema de descontos condicionais (método, prazo, quantidade de parcelas) e regras de sobreposição. Distinto de SPEC-F-004 (programações por período) — aqui são descontos **contingentes à escolha do formando** no ato da adesão.

---

## 1. Conceitos

### 1.1 Tipos de desconto

| Tipo                | Exemplo                                  |
| ------------------- | ---------------------------------------- |
| Por método          | -5% à vista no PIX, -3% no cartão        |
| Por antecipação     | -10% para pagamento à vista, -2% em 3x   |
| Por volume          | -8% se aderir com irmão na mesma família |
| Por convite externo | Cupom promocional (ex: `FORMATURA2026`)  |

### 1.2 Condições de pagamento

Tabela do Contrato define planos disponíveis:

| Modalidade | Parcelas máx | Desconto aplicado |
| ---------- | -----------: | ----------------- |
| À vista    |            1 | -10%              |
| Curto      |        2 a 5 | -5%               |
| Médio      |       6 a 10 | 0%                |
| Longo      |      11 a 12 | +3% (acréscimo)   |

### 1.3 Sobreposição

Quando múltiplos descontos aplicam:

- **Multiplicativos** (default): `preco * (1 - d1) * (1 - d2)`
- **Aditivos** (quando configurado): `preco - (d1 + d2)`
- Cap global: máximo 50% de desconto (configurável por Contrato)

---

## 2. Modelo de dados (preview)

### 2.1 `descontos` — nova tabela

| Campo                                  | Tipo                                                                   |
| -------------------------------------- | ---------------------------------------------------------------------- |
| `id`, `ulid`                           |                                                                        |
| `contrato_id`                          | FK                                                                     |
| `tipo`                                 | enum: `metodo`, `antecipacao`, `volume`, `cupom`                       |
| `valor_percentual` OR `valor_centavos` |                                                                        |
| `condicoes_json`                       | JSONB com regras (ex: `{"metodo_in": ["pix"], "qtd_parcelas_max": 5}`) |
| `codigo_cupom`                         | VARCHAR(30) nullable UNIQUE                                            |
| `data_inicio`, `data_fim`              |                                                                        |
| `max_usos`                             | INTEGER nullable                                                       |
| `max_usos_por_formando`                | default 1                                                              |
| `combinavel_com_outros`                | BOOLEAN                                                                |
| `ativo`                                |                                                                        |

### 2.2 `condicoes_pagamento` — nova tabela

| Campo                                           | Tipo                                         |
| ----------------------------------------------- | -------------------------------------------- |
| `id`, `ulid`                                    |                                              |
| `contrato_id`                                   | FK                                           |
| `nome`                                          | VARCHAR(100) (ex: "À vista", "3x sem juros") |
| `qtd_parcelas_min`, `qtd_parcelas_max`          | SMALLINT                                     |
| `metodos_permitidos_json`                       | JSONB (ex: `["pix","boleto","cartao"]`)      |
| `desconto_percentual` OR `acrescimo_percentual` | DECIMAL(5,2)                                 |
| `ordem_exibicao`                                |                                              |
| `ativa`                                         |                                              |

---

## 3. Resolução de preço total

```
preco_final = preco_programacao(pacote, data)      # de SPEC-F-004
            * aplicar_descontos(contrato, plano_pagamento)   # aqui
```

Detalhes em SPEC-F-006 (cálculo de parcelas).

---

## 3.1 Regras por método de pagamento (exemplos de `condicoes_pagamento`)

Referência operacional: regras de parcelamento e desconto por método de pagamento. Valores
ilustrativos; cada contrato pode customizar via admin. Seed de desenvolvimento deve popular essas
condições no `DevelopmentSeeder`.

| Método | 1ª parcela permite? | Demais permite? | Parcelas min/max  | Desconto / Acréscimo por faixa                     |
| ------ | ------------------- | --------------- | ----------------- | -------------------------------------------------- |
| PIX    | ✅                  | ❌ (bloqueado)  | 1 (à vista)       | 1x: **−10%**                                       |
| Boleto | ✅                  | ✅              | 1–12              | 1x: −10% · 2–5x: −5% · 6–10x: 0% · 11–12x: **+3%** |
| Cartão | ✅                  | ✅              | 2–12 (**sem 1x**) | 2–5x: −5% · 6–10x: 0% · 11–12x: **+3%**            |

Regras de validação (backend + frontend):

- `metodo_primeira_parcela` ∈ `{pix, boleto, cartao}` — PIX permitido.
- `metodo_demais` ∈ `{boleto, cartao}` — **PIX sempre bloqueado** em "demais parcelas".
- Se `metodo_primeira_parcela='pix'` ⇒ `qtd_parcelas=1` obrigatoriamente (não existe PIX parcelado).
- Se `metodo_primeira_parcela='cartao'` ⇒ `qtd_parcelas≥2`.
- Desconto/acréscimo aplicado ao total antes da divisão em parcelas (ver SPEC-F-006).
- Cap global de desconto (`cap_desconto_percentual`) configurável por contrato, default 50%.

Exemplo de seed `condicoes_pagamento` para um contrato:

```php
// database/seeders/DevelopmentSeeder.php (trecho)
$contrato->condicoesPagamento()->createMany([
    ['nome' => 'À vista PIX',          'qtd_parcelas_min' => 1,  'qtd_parcelas_max' => 1,  'metodos_permitidos_json' => ['pix'],             'desconto_percentual' => 10.00],
    ['nome' => 'À vista boleto',       'qtd_parcelas_min' => 1,  'qtd_parcelas_max' => 1,  'metodos_permitidos_json' => ['boleto'],          'desconto_percentual' => 10.00],
    ['nome' => 'Curto prazo (2–5x)',   'qtd_parcelas_min' => 2,  'qtd_parcelas_max' => 5,  'metodos_permitidos_json' => ['boleto','cartao'], 'desconto_percentual' => 5.00],
    ['nome' => 'Médio prazo (6–10x)',  'qtd_parcelas_min' => 6,  'qtd_parcelas_max' => 10, 'metodos_permitidos_json' => ['boleto','cartao'], 'desconto_percentual' => 0.00],
    ['nome' => 'Longo prazo (11–12x)', 'qtd_parcelas_min' => 11, 'qtd_parcelas_max' => 12, 'metodos_permitidos_json' => ['boleto','cartao'], 'acrescimo_percentual' => 3.00],
]);
```

---

## 4. Pontos a expandir na versão `draft`

- [ ] Decisão final: aditivo ou multiplicativo (padrão)
- [ ] Validação de cupom (anti-reuso, anti-expirado, anti-limite)
- [ ] Policy: só admin cria descontos globais; comissão pode sugerir
- [ ] Auditoria: todo uso de cupom loga `causer + subject + properties.cupom_codigo`
- [ ] Endpoint `POST /api/v1/adesoes/validar-cupom` retorna desconto resolvido + condições
- [ ] Testes: cap de 50%, stacking multiplicativo, cupom expirado, volume de irmãos (integra SPEC-F-003)
- [ ] UX admin: tela de criação com preview de impacto no ticket médio

---

## 5. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §7 — conceito original
- [`SPEC-F-004`](SPEC-F-004-programacoes-valor.md) — precede na resolução de preço
- [`SPEC-F-006`](SPEC-F-006-calculo-parcelas.md) — consome o preço final

---

_**Estado:** `stub`._
