# SPEC-011 — Condição de Pagamento Composta (Boleto + Cartão)

> **Status:** Implementação inicial. Próxima fase: pagamento online do bloco-cartão.
> **Última atualização:** 2026-04-25

---

## 1. Contexto

A condição de pagamento da plataforma (`condicoes_pagamento`) suportava, até esta SPEC, um único método uniforme por adesão (PIX, Boleto **ou** Cartão). O negócio precisa oferecer um arranjo onde **uma mesma adesão tenha boletos + parcelas de cartão**, em que:

- O bloco-cartão (qtd e valor por parcela) é **fixo**, configurado pela equipe administrativa por condição.
- Os boletos absorvem o restante do preço do pacote, distribuídos do mês corrente até um **mês-limite** (cutoff) configurado.
- O bloco-cartão **ainda não passa online**: aparece na lista de parcelas como "Cartão de Crédito" e o portal não emite boleto para essas parcelas. O pagamento é combinado presencialmente. A arquitetura está pronta para, no futuro, liberar pagamento online deste bloco sem mudar a UI.

---

## 2. Regras de Negócio

### 2.1 Composição

- Cada condição composta tem **um bloco-cartão fixo** (`cartao_qtd_parcelas` × `cartao_valor_parcela_centavos`).
- O valor total dos boletos = `total_liquido_pacote − cartao_total`.
- A quantidade de boletos é **derivada** da diferença entre o mês corrente (de adesão) e o `boleto_data_fim` configurado, **inclusiva**.
- Boleto da 1ª iteração absorve qualquer resto da divisão `boleto_total / qtd_boletos`.

### 2.2 Adesão atrasada — redistribuição

Quando a formanda adere depois do mês de início configurado, os boletos cujo vencimento já passou desaparecem. O **valor total dos boletos permanece o mesmo**, redistribuído entre os meses restantes. O bloco-cartão fica intacto.

> Exemplo: pacote R$2.400 com 12 boletos R$150 + 6 cartão R$100. Adesão em abr/2026 (perdeu fev/mar) → restam 11 boletos. Cartão fixo R$600. Boletos totalizam R$1.800 → cada boleto = R$163,63 (1ª parcela = R$163,70 com resto). Total final: R$2.400.

### 2.3 Vencimento dos boletos

- Cada condição traz `dias_vencimento_permitidos_json` (lista de inteiros 1-31). **Default `[30]`**.
- A formanda escolhe um dos dias permitidos. Se a lista tiver tamanho 1, a SPA esconde o picker e exibe "Vencimento: dia X" estático.
- Ao gerar o vencimento, o sistema faz **clamp para o último dia do mês** quando o mês não tem o dia escolhido (ex.: 30 em fevereiro → 28/29).

### 2.4 Restrições

- Combinação válida: **só boleto + cartão**. PIX fica fora do tipo composto.
- `metodos_permitidos_json` deve ser exatamente `['boleto','cartao']` (CHECK no Postgres).
- `cartao_qtd_parcelas`, `cartao_valor_parcela_centavos`, `boleto_data_fim` obrigatórios em condição composta; nulos em condição normal (CHECK).
- Erro `CondicaoPagamentoInvalidaException` quando `qtd_boletos < 1` (cutoff já passou) ou `boleto_total ≤ 0` (cartão configurado supera o pacote).
- Múltiplas condições compostas podem coexistir num mesmo contrato.

---

## 3. Modelo de Dados

### 3.1 Tabela `condicoes_pagamento` — colunas adicionadas

| Coluna                            | Tipo            | Default    | Observação                           |
| --------------------------------- | --------------- | ---------- | ------------------------------------ |
| `tipo`                            | `varchar(20)`   | `'normal'` | enum `TipoCondicaoPagamento`         |
| `cartao_qtd_parcelas`             | `smallint` null | —          | obrigatório quando `tipo='composta'` |
| `cartao_valor_parcela_centavos`   | `int` null      | —          | obrigatório quando `tipo='composta'` |
| `boleto_data_fim`                 | `date` null     | —          | armazena 1º dia do mês-cutoff        |
| `dias_vencimento_permitidos_json` | `jsonb` null    | `[30]`     | lista de inteiros 1-31               |

**CHECKs Postgres:**

- `tipo IN ('normal','composta')`
- Coerência `tipo` ↔ campos cartão (todos nulos para normal; todos não-nulos para composta)
- Coerência `tipo='composta'` ↔ `metodos_permitidos_json = ['boleto','cartao']`
- `cartao_qtd_parcelas >= 1` e `cartao_valor_parcela_centavos >= 1` quando preenchidos
- `jsonb_array_length(dias_vencimento_permitidos_json) >= 1`

### 3.2 Tabela `parcelas` — coluna adicionada

| Coluna                  | Tipo      | Default | Observação                                         |
| ----------------------- | --------- | ------- | -------------------------------------------------- |
| `permite_emitir_boleto` | `boolean` | `true`  | `false` para parcelas de cartão de adesão composta |

A SPA usa essa flag para **ocultar** o botão "Emitir boleto/2ª via" — sem badge, sem tooltip. Demais parcelas (boletos da composta + parcelas de condições normais) mantêm `true`.

### 3.3 Enum `App\Enums\Pagamento\TipoCondicaoPagamento`

```php
enum TipoCondicaoPagamento: string {
    case Normal = 'normal';
    case Composta = 'composta';
}
```

---

## 4. Algoritmo do Simulador Composto

`App\Actions\Adesao\SimularParcelasAction::simularComposta(...)`:

```text
totalLiquido          = aplicarDescontoOuAcrescimo(precoCentavos, condicao)
cartaoTotal           = cartao_qtd_parcelas × cartao_valor_parcela_centavos
boletoTotal           = totalLiquido − cartaoTotal           [erro se ≤ 0]
referencia            = now().startOfMonth()
qtdBoletos            = mesesInclusivos(referencia, boleto_data_fim)   [erro se < 1]
valorBaseBoleto       = intdiv(boletoTotal, qtdBoletos)
restoBoleto           = boletoTotal mod qtdBoletos

para i de 0 até qtdBoletos − 1:
    valor      = i==0 ? valorBaseBoleto + restoBoleto : valorBaseBoleto
    vencimento = clampLastDay(referencia.addMonths(i), diaVencimento)
    cria parcela boleto, permite_emitir_boleto=true

para i de 0 até cartao_qtd_parcelas − 1:
    vencimento = clampLastDay(referencia.addMonths(qtdBoletos + i), diaVencimento)
    cria parcela cartão, permite_emitir_boleto=false
```

`SimulacaoResultData` retorna campos extras: `qtdBoletos`, `qtdCartao`, `cartaoTotalCentavos`.

---

## 5. Contrato API

### 5.1 `GET /api/v1/adesao/publico/iniciar` — payload das condições

Cada condição na resposta agora traz:

```json
{
    "ulid": "01H...",
    "tipo": "composta",
    "nome": "Composto: Boleto + 6x cartão (R$100)",
    "qtd_parcelas_min": 1,
    "qtd_parcelas_max": 60,
    "metodos_permitidos": ["boleto", "cartao"],
    "desconto_percentual": 0,
    "acrescimo_percentual": 0,
    "cartao_qtd_parcelas": 6,
    "cartao_valor_parcela_centavos": 10000,
    "boleto_data_fim": "2027-04-01",
    "dias_vencimento_permitidos": [30]
}
```

Para condições normais, os campos `cartao_*` e `boleto_data_fim` vêm `null`; `dias_vencimento_permitidos` traz a lista configurada (default `[30]`).

### 5.2 `POST /api/v1/adesao/publico/simular`

- **Normal**: payload mantém `condicao_ulid`, `qtd_parcelas`, `metodo_primeira_parcela`, `metodo_demais_parcelas`, `dia_vencimento`.
- **Composta**: payload envia apenas `condicao_ulid` e `dia_vencimento`. O FormRequest aceita `qtd_parcelas` e métodos como `nullable` quando a condição é composta.

Resposta agora inclui `qtd_boletos`, `qtd_cartao`, `cartao_total_centavos` (nulos em condição normal). Cada parcela traz `permite_emitir_boleto: boolean`.

### 5.3 `POST /api/v1/adesao/publico/commit`

Mesma semântica do simular: `qtd_parcelas`/métodos opcionais quando composta. `dia_vencimento` precisa estar em `dias_vencimento_permitidos_json` da condição (validação no FormRequest).

---

## 6. Frontend SPA

### 6.1 Tipos (`resources/spa/src/api/types.ts`)

- `CondicaoPagamento`: novos campos `tipo`, `cartao_qtd_parcelas`, `cartao_valor_parcela_centavos`, `boleto_data_fim`, `dias_vencimento_permitidos`.
- `Parcela`: novo campo opcional `permite_emitir_boleto`.
- `SimulacaoResult`: novos campos `qtd_boletos`, `qtd_cartao`, `cartao_total_centavos`.
- `CommitPayload`: `qtd_parcelas`, `metodo_primeira_parcela`, `metodo_demais_parcelas` agora opcionais.

### 6.2 Componentes

- **`DiaVencimentoPicker`**: recebe `diasPermitidos: number[]`. Se `length>1`, renderiza radio-group como hoje. Se `length===1`, exibe display estático "Dia X de cada mês".
- **`CompostaDetalhes`**: novo componente — picker de condição composta + resumo do bloco-cartão + `DiaVencimentoPicker` + nota explicativa.
- **`BoletoDetalhes`** e **`CartaoDetalhes`**: filtram condições por `tipo === 'normal'`. `BoletoDetalhes` agora usa `DiaVencimentoPicker` em vez do array hard-coded `[1,5,10,15,20,25]`.
- **`StepConfirmar`**: 4 cards de pagamento (PIX, Boleto, Cartão, Boleto+Cartão). O card composto só aparece se houver alguma condição com `tipo='composta'`. Estado interno `viewMode: 'pix'|'boleto'|'cartao'|'composta'` separado de `metodoPrimeira` (que sempre é `pix|boleto|cartao` para o backend).
- **`ResumoSimulacao`**: datas em pt-BR (`dd/mm/yyyy`) via `formatDataBr()`. Quando composta, exibe "Nx boleto + Mx cartão de crédito" abaixo do total. Sem badge "Presencial" — parcelas de cartão aparecem com label `Cartão de Crédito`.

### 6.3 Helper

- **`formatDataBr(input)`** em `resources/spa/src/lib/utils.ts` — converte string ISO ou `Date` para `dd/mm/yyyy`.

### 6.4 Área logada da formanda

A área logada ainda não está construída. Quando construída, deve **ocultar** ações de "Emitir boleto" / "2ª via" quando `parcela.permite_emitir_boleto === false`. Demais ações (visualizar, ver valor) seguem normais. Datas em pt-BR.

---

## 7. Testes

- **`tests/Unit/Actions/Adesao/SimularParcelasCompostaTest`**: 8 testes cobrindo cutoff no futuro, redistribuição em adesão atrasada, clamp de fevereiro, cartão > pacote, cutoff passado, dia inválido, sequência cartão após boleto, flag `permite_emitir_boleto`.
- **`tests/Unit/Actions/Adesao/SimularParcelasActionTest`** (regressão): mantém todos os 15 testes verdes; signature do action preservada.

---

## 8. Porta Aberta — Pagamento Online do Bloco-Cartão

Quando o fluxo de pagamento online do bloco-cartão for implementado:

1. Endpoint `POST /api/v1/parcelas/{ulid}/intent-cartao` que cria intent no gateway para o bloco-cartão (R$ N×100 = R$X em N parcelas no cartão real).
2. Após sucesso, alterar `parcelas.permite_emitir_boleto` para `false` continua, mas surge nova capability — a UI passa a exibir botão "Pagar no cartão" para essas parcelas. Nada na lógica de geração precisa mudar.
3. Avaliar criar coluna `parcelas.pagavel_online` (boolean) ou deduzir de `metodo_preferido='cartao'` + flag de habilitação na condição.

---

## 9. Referências

- Migrations: `database/migrations/2026_04_25_180000_add_composta_fields_to_condicoes_pagamento_table.php`, `2026_04_25_180100_add_permite_emitir_boleto_to_parcelas_table.php`
- Action: `app/Actions/Adesao/SimularParcelasAction.php`
- Enum: `app/Enums/Pagamento/TipoCondicaoPagamento.php`
- Seeder: `database/seeders/CondicaoPagamentoSeeder.php` (condição "Composto: Boleto + 6x cartão (R$100)" para CTR-ATIVO-2027)
- Componentes SPA: `resources/spa/src/components/wizard/pagamento/{CompostaDetalhes,DiaVencimentoPicker,BoletoDetalhes,ResumoSimulacao}.tsx`
- Plano de implementação: `~/.claude/plans/a-buzzing-moon.md`
