---
title: 'ADR-0014: Valores monetários em INTEGER centavos'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0014: Valores monetários em INTEGER centavos

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Financeiro | **Tags:** dinheiro, tipagem, postgres

## Contexto e problema

O domínio envolve valores financeiros críticos: preço de pacote, valor de adesão, parcelas, pagamentos, pedido extra, snapshot comercial, reembolso. Representar dinheiro como `float` em PHP/Postgres gera arredondamentos imprecisos (`0.1 + 0.2 !== 0.3`), disputas em centavos, problemas em cálculo de parcelas (`1299 / 10` em float vira `129.89999...`).

## Drivers da decisão

- Exatidão: cliente não aceita 1 centavo errado em 10.000.
- Cálculo de parcelamento (divisão, resto, ajuste de última parcela).
- Auditoria: soma de parcelas deve bater com total exato.
- Compatibilidade com gateway de pagamento (Itaú espera `valor_centavos` BIGINT na API).
- Serialização JSON estável (número inteiro é determinístico).

## Alternativas consideradas

### Alt 1: `DECIMAL(12,2)` Postgres + string em PHP

- Prós: semântica "natural" de dinheiro.
- Contras: cálculo requer libs (`bcmath`, `Brick\Money`); mais ida-e-volta; risco de conversão implícita para float em alguns paths; comparações mais frágeis.

### Alt 2: `FLOAT`/`DOUBLE`

- Prós: simples.
- Contras: erros de arredondamento, documentados há décadas. Proibido (§0 princípio 3, §19 CLAUDE.md #3).

### Alt 3: `INTEGER`/`BIGINT` em centavos (escolhida)

- Prós: exatidão total (inteiros); operações nativas; fácil serialização; alinhado com gateways; PHP `int` em 64-bit suporta valores até ~9×10^18, muito além de qualquer valor real.
- Contras: dev precisa converter para exibição (`MoneyHelper::format(150099) → "R$ 1.500,99"`) e conversão na entrada (`MoneyHelper::toCents("1.500,99") → 150099`).

## Decisão

**Todo valor monetário é armazenado e transportado como `INTEGER` (ou `UNSIGNED INTEGER`/`BIGINT` conforme ordem de grandeza) representando centavos**. Exemplo: `R$ 1.500,99` → `150099`.

Regras de uso:

1. **Migrations**: `$table->unsignedInteger('valor_total_centavos')`, `$table->unsignedInteger('valor_parcela_centavos')`, etc.
2. **Models**: sem cast especial — PHP int nativo.
3. **DTOs**: propriedades `public readonly int $valorCentavos`.
4. **Resources**: expor sempre `"valor_centavos": 150099` na API (nunca formatar como string em JSON).
5. **Formatação para UI** fica na camada apresentação (`MoneyHelper::format`), com localização PT-BR.
6. **Entrada do usuário** via FormRequest: campo `valor_brl` string ("1.500,99") → transformado por helper em centavos antes da Action.
7. **Cálculo de parcelas**: algoritmo inteiro (`intdiv($total, $n)` + ajuste da última parcela com o `$total % $n` remanescente), evitando qualquer float.
8. **Integração com gateway**: passar `valor_centavos` direto no payload; validação na DTO.

Enum/value-object custom `Money` é rejeitado no MVP por over-engineering; `int` nativo + helper estático cobre o caso com simplicidade.

## Consequências positivas

- Zero erro de arredondamento na aplicação.
- Soma de parcelas sempre bate com o total exato.
- Interoperabilidade com gateway (`amount: 150099`).
- Testes determinísticos.
- JSON estável.

## Consequências negativas

- Todo dev precisa lembrar de multiplicar/dividir por 100 na camada de apresentação. Mitigação: helpers `MoneyHelper` centralizados; arch test Pest que proíbe `float` em `App\Models\*` / `App\Data\*` para campos `*_centavos`.
- Exports (Excel, PDF) precisam formatação explícita — já é obrigatório por PT-BR.

## Ligações

- §0 princípio 3 (CLAUDE.md §7.3 e §19), §4.1, §4.6 do PLANEJAMENTO_BACKEND_APIV1.md
- Apêndice D #1 (não literal aqui, mas princípio consistente)
- ADR-0009 (snapshots), ADR-0010 (enums)
- SAD arc42 seção "Conceitos de corte transversal — Tipagem de domínio"
