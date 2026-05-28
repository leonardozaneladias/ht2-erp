# Módulo: Cálculo Dinâmico de Parcelas

**Sprint:** 6 (Portal) + reforços Sprint 20-23 (Admin)
**Última Atualização:** —
**Status:** 🔴 Pendente
**Referência PRD:** Seção 9 (Cálculo Dinâmico de Parcelas e Vencimentos)

## Escopo

_A definir durante a implementação._ Núcleo de negócio: Services stateless que calculam parcelamento, descontos, datas de vencimento e valores finais a partir de programações, condições e modalidades. API-Ready (aceita dados tipados, retorna DTO).

## Models Envolvidos

| Model | Tabela | Campos Principais |
| ----- | ------ | ----------------- |
| —     | —      | —                 |

## Services e Actions

| Classe                        | Método Principal | Responsabilidade                              |
| ----------------------------- | ---------------- | --------------------------------------------- |
| ParcelamentoCalculatorService | calcular()       | Calcula cronograma completo de parcelas       |
| ProgramacaoAtivaService       | buscarVigente()  | Resolve programação válida para data          |
| DescontoAplicavelService      | resolver()       | Aplica regras de desconto escalonado          |
| PrimeiroVencimentoService     | calcular()       | Define primeiro vencimento respeitando margem |

## Rotas

| Método | URI | Controller@Action | Middleware |
| ------ | --- | ----------------- | ---------- |
| —      | —   | —                 | —          |

## Components Blade Utilizados

- —

## Regras de Negócio

1. Valores monetários SEMPRE em centavos (`int`)
2. Primeiro vencimento respeita margem mínima de dias do checkout
3. Redução de parcelas por meses transcorridos da programação
4. Suporte a modalidade híbrida (entrada + parcelas)
5. Retorno via `ParcelamentoCalculoDTO` (readonly, com `toArray()`)

## Telas / UI

—

## Testes

| Teste                                              | Tipo | Cenário                      |
| -------------------------------------------------- | ---- | ---------------------------- |
| test_calculo_parcela_reduz_por_meses_transcorridos | Unit | Redução proporcional         |
| test_adesao_rejeita_programacao_expirada           | Unit | Programação fora de vigência |

Mínimo de 15 cenários (crítico — ver `CONVENTIONS.md §6.3`).

## Dependências

- Depende de: Programações de Valor (06), Condições de Pagamento (07), Descontos (08)
- Dependido por: Adesão Wizard (11), Extras (17), Parcelas Financeiro (14)

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
