# 07 — Jornada, Horas Extras e Fundação de Folha

> Mecânica de **jornada/escalas**, **cálculo e aprovação de horas extras** com **snapshot imutável**, e a **fundação de folha** (catálogo `rubricas` + referência `tabelas_legais`) do módulo de RH. Este documento detalha fórmulas, máquina de estados, configuração e exemplos numéricos; **não redefine schema** — os nomes de tabelas, colunas, enums e permissões são da fonte de verdade [01 — Modelo de Domínio](01-modelo-de-dominio.md), e qualquer divergência se corrige **lá primeiro**.
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · `packages/extensao-rh/` · views `rh::` · multi-tenant lógico por `empresa_id` · banco **PostgreSQL 16** · dinheiro em **INTEGER centavos** ([ADR-0014](../../../architecture/adrs/ADR-0014-money-integer-centavos.md)), duração em **minutos inteiros**.

Relacionados: [01](01-modelo-de-dominio.md) · [04](04-catalogos-configuraveis.md) · [05](05-organograma-acl-hierarquica.md) · [09](09-roadmap-fases.md) · [adrs/ADR-RH-004](adrs/ADR-RH-004-jornada-horas-extras-folha.md)

---

## 0. Fronteira de escopo (leia primeiro)

A Fase 1 entrega **cálculo de hora extra** + a **fundação** de folha. Ela **não** entrega a apuração mensal nem a folha de pagamento. A distinção é deliberada e estrutura todo este documento.

|                       | **A Fase 1 ENTREGA** (este doc)                                                                                                                                                                 | **A Fase 1 NÃO entrega** (Fase 3+, ver [09](09-roadmap-fases.md))                                                                                       |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Jornada               | `escalas`/`escala_dias`/`escala_funcionario`; carga semanal/diária derivada; valor-hora                                                                                                         | —                                                                                                                                                       |
| Hora extra            | lançamento, cálculo (fator × base × duração), **workflow de aprovação**, snapshot imutável do cálculo                                                                                           | —                                                                                                                                                       |
| Folha — **estrutura** | catálogo `rubricas` (proventos/descontos/informativas, incidências INSS/FGTS/IRRF); referência `tabelas_legais` (faixas por vigência); HE aprovada **vira rubrica** (`horas_extras.rubrica_id`) | —                                                                                                                                                       |
| Folha — **apuração**  | —                                                                                                                                                                                               | apuração mensal por competência; holerite PDF; INSS/FGTS/IRRF **aplicados**; 13º/férias; fechamento de competência; eSocial transmitido (S-1200/S-1210) |

**O que o número da HE significa na Fase 1.** O `valor_calculado_centavos` de uma HE aprovada é, ao mesmo tempo, (a) uma **base de custo gerencial** — quanto aquela hora extra custa à empresa, visível em relatórios — e (b) um **insumo congelado** que a folha futura (Fase 3) somará por competência e sobre o qual aplicará as `tabelas_legais`. Ele **não** é um valor líquido a pagar, nem passou por INSS/IRRF: é o valor bruto da rubrica de HE. A apuração que transforma rubricas em holerite é Fase 3.

> **Por que essa fronteira existe.** A folha brasileira completa (encargos progressivos, 13º, férias, eSocial) é a Fase 3+ por complexidade legal crescente (ver a tabela de fases em [09 §1](09-roadmap-fases.md)). A Fase 1 constrói o **contrato imutável** — rubricas, parâmetros legais por vigência e snapshots de cálculo — que a Fase 3 consome **sem retrabalho de schema**. Ponto eletrônico (origem automática do tempo) é Fase 5/6; até lá, a HE é lançada manualmente pelo gestor.

---

## 1. Jornada e escalas

### 1.1 As três peças

Uma jornada é modelada por três tabelas tenant-scoped (catálogo descrito na visão de produto em [04 §6](04-catalogos-configuraveis.md); aqui está a mecânica de cálculo):

```
escalas (cabeçalho)
  └─< escala_dias        (1 linha por dia × turno; a jornada concreta)
escala_funcionario       (atribui uma escala a um funcionário, com vigência = histórico SCD)
```

- **`escalas`** — cabeçalho reutilizável: `nome`, `tipo` (`TipoEscala`), `carga_semanal_minutos` (cache, conferido na escrita), `horas_mensais_divisor` (SMALLINT, default 220 — base do valor-hora).
- **`escala_dias`** — **uma linha por dia×turno**: `dia_semana` (`DiaSemana`, **ISO 1=segunda … 7=domingo**), `ordem_turno` (SMALLINT, 1=manhã, 2=tarde…), `eh_folga` (BOOL), `entrada`/`saida` (TIME). Unique `(escala_id, dia_semana, ordem_turno)`; CHECK `eh_folga OR (entrada IS NOT NULL AND saida IS NOT NULL)`.
- **`escala_funcionario`** — atribuição com `vigencia_inicio`/`vigencia_fim` (`null` = vigente). **SCD** (slowly changing dimension): no máximo **uma vigência aberta** por funcionário, garantida por índice parcial Postgres `UNIQUE (funcionario_id) WHERE vigencia_fim IS NULL`.

### 1.2 Conceitos de modelagem da jornada

**Intervalo (almoço) = lacuna entre turnos.** Não há coluna de intervalo. Um dia com almoço é **dois turnos** no mesmo `dia_semana`: o intervalo é o tempo entre o `saida` do turno 1 e a `entrada` do turno 2. Exemplo de uma segunda-feira 8h–12h / 13h–18h (1h de almoço, 9h trabalhadas):

| dia_semana  | ordem_turno | eh_folga | entrada | saida |
| ----------- | ----------- | -------- | ------- | ----- |
| 1 (segunda) | 1           | false    | 08:00   | 12:00 |
| 1 (segunda) | 2           | false    | 13:00   | 18:00 |

A duração trabalhada do dia é a **soma das durações dos turnos** (4h + 5h = 9h = 540 min), **excluindo** a lacuna do almoço — o intervalo não é tempo trabalhado.

**Turno que cruza a meia-noite.** Quando `saida < entrada`, o turno termina **no dia seguinte**. A duração é `(24:00 − entrada) + saida`. Exemplo de um turno noturno 22:00 → 06:00:

```
duração = (24:00 − 22:00) + 06:00 = 2h + 6h = 8h = 480 minutos
```

Em minutos, com `entrada`/`saida` convertidos para minutos desde a meia-noite (`hh*60+mm`):

```
dur_turno_min = (saida_min >= entrada_min)
              ? (saida_min - entrada_min)                 // turno normal
              : (1440 - entrada_min) + saida_min          // cruza meia-noite
```

### 1.3 Carga semanal e diária derivadas

A `carga_semanal_minutos` **não é digitada** — é **derivada dos turnos** e conferida na escrita (a Action de gravação da escala recalcula e compara/cacheia):

```
carga_diaria(dia)    = Σ dur_turno_min  para todos os turnos não-folga daquele dia_semana
carga_semanal_minutos = Σ carga_diaria(dia)  para dia_semana ∈ {1..7}
```

Pseudocódigo da Action (`RecalcularCargaEscala`):

```
total = 0
para cada dia_semana em 1..7:
    para cada turno (ordem_turno) daquele dia, com eh_folga = false:
        total += dur_turno_min(turno.entrada, turno.saida)
escala.carga_semanal_minutos = total   // cache; conferido contra o digitado, se houver
```

> A carga é **cache desnormalizado**: a verdade está nas linhas de `escala_dias`. Toda escrita de `escala_dias` dispara o recálculo na mesma transação (mesmo padrão das colunas "atuais" de `funcionarios` vs `funcionario_eventos`).

### 1.4 Jornadas brasileiras de referência (seeds)

As escalas semeadas por empresa ([01 §5](01-modelo-de-dominio.md)) cobrem os arranjos típicos da CLT. Cada uma demonstra um padrão de modelagem:

| Escala (seed)                 | `tipo`             | Arranjo de dias × turnos                                   | Carga semanal           | Divisor sugerido             |
| ----------------------------- | ------------------ | ---------------------------------------------------------- | ----------------------- | ---------------------------- |
| **44h Seg–Sex+Sáb** (5×8 + 4) | `semanal`          | Seg–Sex 08:00–12:00/13:00–17:00 (8h); Sáb 08:00–12:00 (4h) | 44h00 = 2 640 min       | 220                          |
| **44h "8h48"** (5 dias)       | `semanal`          | Seg–Sex 08:00–12:00/13:00–17:48 (8h48); Sáb folga          | 44h00 = 2 640 min       | 220                          |
| **40h Seg–Sex**               | `semanal`          | Seg–Sex 08:00–12:00/13:00–17:00 (8h); Sáb/Dom folga        | 40h00 = 2 400 min       | 200 (ou 220 fixo — ver §2.3) |
| **12×36 Diurno**              | `doze_trinta_seis` | dias de trabalho 07:00–19:00 (12h); demais folga           | varia conforme o ciclo¹ | 220                          |
| **12×36 Noturno**             | `doze_trinta_seis` | dias de trabalho 19:00–07:00 (cruza meia-noite, 12h)       | varia conforme o ciclo¹ | 220                          |
| **Estágio 6h**                | `parcial`          | Seg–Sex 13:00–19:00 (6h, sem intervalo obrigatório < 6h)   | 30h00 = 1 800 min       | 150 (estágio)                |
| **Parcial 30h**               | `parcial`          | Seg–Sex 08:00–14:00 (6h)                                   | 30h00 = 1 800 min       | 150                          |

> ¹ **12×36 e a semana ISO.** A escala 12×36 não cabe numa semana fixa de 7 dias (o ciclo é "12h trabalhadas, 36h de folga", deslocando-se ao longo das semanas). Na Fase 1 modela-se o **padrão de turno** (12h, diurno ou noturno) em `escala_dias`; a **distribuição real** dos dias trabalhados ao longo do mês é apurada pela escala vigente na data + o calendário (Fase 2 traz feriados/calendário de equipe). Para o cálculo de **valor-hora**, o que importa é o **divisor** (§2), não a carga semanal — por isso 12×36 usa `modo_divisor = fixo_220`.

**Conferência na escrita.** Além de cachear a carga, a Action valida coerência por `tipo`: uma `semanal` com 80h semanais é rejeitada (limite legal 44h + tolerância configurável); uma `parcial` acima de 30h emite alerta (a Lei 13.467/2017 fixa o teto do regime parcial). Essas validações são **avisos/erros de UI** na Fase 1, não bloqueio fiscal.

### 1.5 Vigência e "qual escala valia naquela data"

`escala_funcionario` é **histórico**, não estado atual. Para qualquer cálculo de uma **data passada** (e o cálculo de HE sempre é), resolve-se a escala que **estava vigente naquela data**:

```sql
-- escala vigente para o funcionário F na data D
SELECT ef.escala_id
FROM   escala_funcionario ef
WHERE  ef.funcionario_id = :F
  AND  ef.vigencia_inicio <= :D
  AND  (ef.vigencia_fim IS NULL OR ef.vigencia_fim >= :D)
ORDER BY ef.vigencia_inicio DESC
LIMIT 1;
```

A regra **uma vigência aberta** + a validação de **não-sobreposição** na Action garantem resultado único. Se o funcionário troca de escala em 01/06, uma HE lançada com `data = 28/05` usa a escala **anterior** — exatamente como o valor-hora dela deve ser apurado pelo salário/escala daquela época (ver o preview "ao vivo" em §4.2 e o snapshot em §4).

---

## 2. Valor-hora

O valor-hora é a **base** sobre a qual o fator da HE incide. Tudo em **centavos inteiros** ([ADR-0014](../../../architecture/adrs/ADR-0014-money-integer-centavos.md)); `round` aparece **só** na divisão final.

### 2.1 Mensalista

```
valor_hora_centavos = round(salario_base_centavos / divisor)
```

O `divisor` é o número de horas mensais "praxe" — quantas horas o salário mensal "compra". A praxe CLT para 44h semanais é **220** (44h × 5 semanas ≈ 220h; consolidado pela jurisprudência), e é o **default**.

### 2.2 Horista

O salário **já é** o valor-hora — não há divisão:

```
valor_hora_centavos = salario_base_centavos   // salario_tipo = 'horista'
```

O `RegimeTrabalho` dirige qual ramo se aplica: `RegimeTrabalho::baseCalculoHoraExtra()` retorna se a base vem de divisão (mensalista) ou é direta (horista/diarista). Ver o enum em [01 §4](01-modelo-de-dominio.md).

### 2.3 De onde vem o divisor (precedência) e o `modo_divisor`

O divisor é resolvido por **precedência**, do mais específico ao mais geral:

```
1º  escalas.horas_mensais_divisor      (por escala — vence se preenchido)
2º  config('rh.calculo.divisor_horas_mensais')   (default do tenant/app)
3º  220                                 (fallback final, praxe CLT 44h)
```

Há ainda uma opção de **modo** de cálculo do divisor, `config('rh.calculo.modo_divisor')`:

| `modo_divisor`               | Comportamento                                                                                                                         | Quando usar                                                                                            |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| **`fixo_220`** (recomendado) | usa sempre o divisor resolvido pela precedência acima (tipicamente 220), independente da carga real da escala                         | praxe segura; o que a maioria das empresas e contadores espera; evita disputa sobre "divisor derivado" |
| `derivado_da_carga`          | calcula o divisor a partir da carga semanal da escala: `divisor = round(carga_semanal_minutos / 60 × 5)` (≈ horas/semana × 5 semanas) | quando o cliente exige que jornadas reduzidas (40h, 30h) tenham valor-hora proporcionalmente maior     |

> **Recomendação: `fixo_220`.** O divisor "derivado" parece mais justo, mas abre margem para interpretação (5 semanas? 4,33? incluir DSR?). A praxe consolidada é 220 para 44h, 200 para 40h e 150 para regimes de 30h — valores que o cliente seta **explicitamente** em `escalas.horas_mensais_divisor` por escala, mantendo o `modo_divisor = fixo_220`. Assim o divisor é **um dado configurável e auditável**, não uma fórmula implícita.

### 2.4 Helper de cálculo

```php
// HT2ML\Rh\Support\Calculo\ValorHora
final class ValorHora
{
    /** Retorna o valor-hora em centavos (inteiro). */
    public static function calcular(Funcionario $funcionario, ?Escala $escala): int
    {
        // Horista: o salário já é o valor-hora.
        if ($funcionario->regime_trabalho->baseCalculoHoraExtra() === BaseHoraExtra::DIRETA) {
            return $funcionario->salario_base_centavos;
        }

        // Mensalista: divide pelo divisor resolvido por precedência.
        $divisor = self::resolverDivisor($escala);

        return (int) round($funcionario->salario_base_centavos / $divisor);
    }

    private static function resolverDivisor(?Escala $escala): int
    {
        if (config('rh.calculo.modo_divisor') === 'derivado_da_carga'
            && $escala?->carga_semanal_minutos) {
            return (int) round($escala->carga_semanal_minutos / 60 * 5);
        }

        return $escala?->horas_mensais_divisor
            ?? config('rh.calculo.divisor_horas_mensais')
            ?? 220;
    }
}
```

**Exemplo.** Mensalista, `salario_base_centavos = 330000` (R$ 3.300,00), divisor 220:

```
valor_hora_centavos = round(330000 / 220) = round(1500,0) = 1500   // R$ 15,00/h
```

Mesmo salário numa escala de 40h com divisor 200:

```
valor_hora_centavos = round(330000 / 200) = 1650   // R$ 16,50/h
```

> **Por que inteiro até o fim.** Operar `salario_base_centavos / divisor` em `int`/`round` evita o erro clássico de float (`0.1 + 0.2 ≠ 0.3`). O `round` único na divisão final é o **único** ponto onde se perde precisão — e é onde a praxe contábil também arredonda. Nunca se converte para `float`/`decimal` antes ([ADR-0014 §Decisão](../../../architecture/adrs/ADR-0014-money-integer-centavos.md)).

---

## 3. Horas extras — cálculo

### 3.1 A tabela e os tipos

Uma HE vive em `horas_extras` ([01 §3 D1](01-modelo-de-dominio.md)): `minutos` (INTEGER, CHECK `> 0`), `tipo` (`TipoHoraExtra`), `status` (`StatusHoraExtra`), os campos de snapshot (§4) e o vínculo de workflow (`lancado_por`/`aprovado_por_admin_user_id`). O `tipo` carrega o **fator** em **basis points (bps) inteiros** — um bps é 1/10000, então 50% = 5000 bps, 100% = 10000 bps. Usar bps inteiros mantém a aritmética longe de float.

```php
// HT2ML\Rh\Enums\TipoHoraExtra
enum TipoHoraExtra: string
{
    case HE_50   = 'he_50';     // hora extra 50% (dias úteis)
    case HE_100  = 'he_100';    // hora extra 100% (domingos/feriados)
    case NOTURNA = 'noturna';   // adicional noturno (22h–5h)
    case DSR     = 'dsr';       // DSR sobre HE (reflexo)

    /** Fator PADRÃO em basis points (sobrepujável por empresa — §3.3). */
    public function fatorPadraoBps(): int
    {
        return match ($this) {
            self::HE_50   => 5000,    // +50%
            self::HE_100  => 10000,   // +100%
            self::NOTURNA => 5000,    // +50% sobre a hora (adicional noturno mínimo 20%; ver nota)
            self::DSR     => 0,       // reflexo: o fator vem do cálculo do DSR, não um % fixo
        };
    }

    public function adicionalNoturno(): bool
    {
        return $this === self::NOTURNA;
    }
}
```

> **Sobre os fatores semente.** Os valores acima são **defaults de engenharia** (50% é o mínimo constitucional da HE comum; 100% a praxe para domingos/feriados). O **adicional noturno legal mínimo** é 20% (`config('rh.calculo.adicional_noturno_bps')` default **2000**, ver §3.4) — o `NOTURNA.fatorPadraoBps()` acima existe para o caso de a empresa tratar a hora noturna também como hora extra; quando se trata apenas do adicional, usa-se o bps de config. **DSR** é um reflexo proporcional (HE da semana ÷ dias úteis × repousos), não um percentual fixo: na Fase 1 é modelado como rubrica própria, com o fator efetivo calculado e gravado no snapshot. A fronteira: a Fase 1 grava o número; a apuração do DSR fino é Fase 3.

### 3.2 A fórmula

```
valor_he_centavos = round( valor_hora_centavos × (10000 + fator_bps) / 10000 × minutos / 60 )
```

Lendo em partes:

- `(10000 + fator_bps) / 10000` — multiplicador da hora. HE 50% → `15000/10000 = 1,5`. HE 100% → `2,0`.
- `× minutos / 60` — converte a duração (minutos) em fração de hora.
- `round(...)` — único arredondamento, no fim, para centavos inteiros.

Para preservar a precisão inteira o máximo possível, a implementação acumula em inteiro e divide **uma vez**:

```php
// HT2ML\Rh\Support\Calculo\ValorHoraExtra
$valorHe = (int) round(
    $valorHoraCentavos * (10000 + $fatorBps) * $minutos / (10000 * 60)
);
```

### 3.2.1 Pré-condição: base de cálculo obrigatória (decisão D-HE)

O cálculo exige **base completa na data do lançamento**: (a) `salario_base_centavos` do funcionário e (b) uma **escala vigente** naquela data (para o divisor / valor-hora — §2.3). **Decisão D-HE (resolve [PEND-07](13-rastreabilidade-e-pendencias.md)):** se faltar salário **ou** escala vigente na data, o sistema **bloqueia** o lançamento/aprovação da HE e **alerta** o operador a completar a base — **nunca** estima com valor parcial ou com o último valor conhecido. A guarda é validada na Action/FormRequest do lançamento e **reconfirmada na transição de aprovação** (§5.2), pois o snapshot só pode congelar um valor íntegro (§4). Sem base íntegra, a HE **não sai de `rascunho`**.

### 3.3 Override de fator por empresa (catálogo que sobrepõe o enum)

O enum dá **type-safety** (os tipos de HE são finitos e dirigem lógica → ENUM, ver a fronteira em [04 §1](04-catalogos-configuraveis.md)), mas algumas empresas negociam fatores diferentes (ex.: acordo coletivo com HE a 60%, ou domingo a 110%). A solução **mantém o enum** e adiciona uma **tabela de override de fator por empresa** — um catálogo tenant fino que sobrepõe o default do enum, linha a linha:

```
fator_horas_extras  (catálogo tenant, opcional)
  empresa_id   BIGINT  NOT NULL
  tipo         VARCHAR(24)  NOT NULL   -- valor de TipoHoraExtra
  fator_bps    INTEGER      NOT NULL   -- sobrepõe fatorPadraoBps() para esta empresa
  ativo        BOOLEAN      NOT NULL DEFAULT true
  UNIQUE (empresa_id, tipo) WHERE deleted_at IS NULL
```

> Esta tabela é **formalizada na fonte de verdade** em [01 §A10](01-modelo-de-dominio.md) (catálogo tenant `[E][S][A]`, CHECK `fator_bps >= 0`, e a nota de **vigência opcional** como evolução); o esquema acima é o resumo de cálculo. O CRUD/tela do catálogo é incremento opcional de B6/B7 ([02](02-fase-1-blueprint.md)); permissões `rh.fator_horas_extras.*` em [01 §10](01-modelo-de-dominio.md).

Resolução do fator efetivo (precedência):

```
fator_efetivo_bps(empresa, tipo) =
    override_ativo(empresa, tipo)?.fator_bps   // 1º: override por empresa, se existir e ativo
    ?? tipo.fatorPadraoBps()                    // 2º: default do enum (código)
```

> **Por que assim.** Promover o tipo de HE inteiro a catálogo perderia a lógica (`adicionalNoturno()`, mapeamento para rubrica, validações) que mora no enum. Manter o enum como **fonte da semântica** e um catálogo apenas para o **número do fator** dá o melhor dos dois: type-safety + flexibilidade por empresa, sem `if` por cliente no código. É exatamente o padrão híbrido "linha do cliente, comportamento no código" de [04 §1](04-catalogos-configuraveis.md), aplicado a um único atributo numérico.

### 3.4 Adicional noturno

Horário noturno urbano legal: **22:00 às 05:00**. O adicional é configurável:

```
config('rh.calculo.adicional_noturno_bps')   // default 2000 (= +20%, mínimo legal)
```

O cálculo do adicional noturno usa a mesma fórmula da §3.2, com `fator_bps = adicional_noturno_bps` aplicado **apenas aos minutos dentro da faixa 22h–5h**. A interseção da duração da HE com a janela noturna é calculada em minutos:

```
minutos_noturnos = sobreposição( [entrada_he, saida_he] , janela 22:00–05:00 )
valor_adic_noturno = round( valor_hora_centavos × adicional_noturno_bps / 10000 × minutos_noturnos / 60 )
```

**Hora noturna reduzida (52'30").** A CLT conta a hora noturna como **52 minutos e 30 segundos** (a "hora reduzida"), o que aumenta o número de horas noturnas pagas. Na Fase 1 isso é **opcional**, atrás de uma flag:

```
config('rh.calculo.hora_noturna_reduzida')   // default false (Fase 1)
```

Quando `true`, os `minutos_noturnos` são "esticados" pelo fator `60/52.5 ≈ 1,142857`:

```
minutos_noturnos_ajustados = round(minutos_noturnos × 60 / 52.5)
```

> **Aproximação gerencial na Fase 1.** O tratamento de adicional noturno e hora reduzida aqui é uma **aproximação para custo gerencial**, não a apuração fiscal definitiva. A apuração rigorosa (com prorrogação da jornada noturna, integração com marcações reais de ponto, interação com DSR) é Fase 3/Fase 5. A flag `hora_noturna_reduzida = false` na Fase 1 deixa isso explícito: liga-se quando o cliente exigir maior fidelidade, ciente de que é estimativa até o ponto eletrônico (Fase 5/6) fornecer as batidas reais.

### 3.5 Exemplo numérico completo

Mensalista, salário R$ 3.300,00, escala 44h (divisor 220), uma HE de **90 minutos** a **50%**:

```
1) valor_hora_centavos = round(330000 / 220)            = 1500          (R$ 15,00/h)
2) fator_efetivo_bps   = TipoHoraExtra::HE_50 (sem override) = 5000      (+50%)
3) multiplicador       = (10000 + 5000) / 10000          = 1,5
4) horas               = 90 / 60                          = 1,5 h
5) valor_he_centavos   = round(1500 × 15000 × 90 / (10000 × 60))
                       = round(1500 × 1,5 × 1,5)
                       = round(3375,0)                    = 3375         (R$ 33,75)
```

Conferência intuitiva: 1,5h × R$ 15,00 × 1,5 = R$ 33,75. ✔

Mesma HE, mas a empresa tem **override** de HE_50 para 60% (`fator_bps = 6000`):

```
valor_he_centavos = round(1500 × 16000 × 90 / 600000) = round(3600,0) = 3600   (R$ 36,00)
```

### 3.6 Nota de design — DSR e Adicional Noturno como `TipoHoraExtra` (rótulo/fundação na Fase 1)

`TipoHoraExtra` ([01 §4](01-modelo-de-dominio.md)) tem os casos `noturna` e `dsr` ao lado de `he_50`/`he_100`. **Na Fase 1 eles são rótulo + fundação** — cada um mapeia para uma rubrica via `referencia_he_tipo` (§6.3) e o valor é **lançado pelo gestor**, não derivado por regra fiscal. A semântica real **diverge** da de uma HE comum, e o **cálculo fino é Fase 3** (apuração) / Fase 5 (ponto):

- **DSR sobre HE não é hora trabalhada — é reflexo.** O Descanso Semanal Remunerado sobre horas extras é **proporcional**: (Σ HE da semana ÷ dias úteis) × dias de repouso. Modelá-lo como um `TipoHoraExtra` com `fatorPadraoBps = 0` (§3.1) é **deliberado**: na Fase 1 o DSR é um número **lançado/estimado** e congelado no snapshot, **não** calculado a partir do conjunto de HE da competência. A apuração que deriva o DSR do conjunto de HE é Fase 3.
- **Adicional noturno incide sobre toda a jornada noturna, não só sobre a extra.** O adicional (mín. 20%) aplica-se a **todas** as horas entre 22h–5h, extras ou não. O caso `noturna`/§3.4 cobre o cenário "hora noturna lançada como HE"; o adicional noturno da **jornada normal** (não-extra) é apuração de folha (Fase 3) e depende das **batidas reais** do ponto (Fase 5). A flag `hora_noturna_reduzida` (52'30") fica `false` na Fase 1 pelo mesmo motivo (§3.4).

> **Risco a sinalizar (para quem implementar a folha — Fase 3):** não tratar `dsr`/`noturna` como se fossem somáveis igual a `he_50`/`he_100`. A apuração deve aplicar a **regra real** (DSR como reflexo do conjunto de HE; adicional noturno como incidência sobre a jornada noturna), sob risco de **dupla contagem** ou base de cálculo errada. Os snapshots da Fase 1 preservam o que foi **lançado**; a Fase 3 **recalcula** pela regra — não reaproveita o número ingenuamente. Fronteira é→não é fundação em §6.4.

---

## 4. Snapshot imutável do cálculo (ADR-0009)

### 4.1 O que congela e quando

Uma HE aprovada **não pode mudar de valor retroativamente** — se amanhã o salário do funcionário sobe, ou a empresa muda o fator de HE, a HE já aprovada continua valendo o que valia. Isso é o invariante de imutabilidade do [ADR-0009](../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md): entidades transacionais capturam um `snapshot_*` JSONB **no momento da transição para o estado final**.

Na transição **`lancada → aprovada`**, a Action de aprovação congela:

- `valor_calculado_centavos` (INTEGER) — o valor final em centavos;
- `percentual_aplicado_bps` (INTEGER) — o fator efetivo aplicado (após override);
- `valor_hora_base_centavos` (INTEGER) — a base/hora usada;
- `snapshot_calculo` (JSONB) — a **memória de cálculo** completa: tudo que explica o número.

Regras operacionais (do ADR-0009, aplicadas à HE):

- **Nunca** alterar o snapshot após criado (escreve uma vez, lê muitas).
- **Nunca** consultar o snapshot em `WHERE` de query operacional — é só para auditoria/exibição.
- O snapshot é serializado do **estado resolvido** no momento da aprovação (salário, escala, divisor, fator **daquela data**), não de IDs mestres que podem mudar depois.

### 4.2 Preview "ao vivo" (volátil) vs snapshot (congelado)

Antes de aprovar, o gestor vê um **preview ao vivo** do valor — **volátil**, recalculado a cada abertura da tela, usando **o salário e a escala vigentes na DATA da HE** (§1.5). Esse preview pode mudar se, por exemplo, o salário for corrigido retroativamente antes da aprovação. No instante da aprovação, o valor é **fotografado** em `snapshot_calculo` e **nunca mais muda**.

|               | Preview (antes de aprovar)                          | Snapshot (após aprovar)                      |
| ------------- | --------------------------------------------------- | -------------------------------------------- |
| Persistência  | não persistido (calculado on-the-fly)               | gravado em `snapshot_calculo` + colunas      |
| Volatilidade  | **volátil** (reflete salário/escala vigentes agora) | **imutável** (ADR-0009)                      |
| Base temporal | salário/escala vigentes **na data da HE**           | idem, **congelados no momento da aprovação** |
| Uso           | conferência do gestor antes de decidir              | base de custo + insumo de folha; auditoria   |

### 4.3 Exemplo do JSON

```json
{
    "versao_engine": "1.0.0",
    "calculado_em": "2026-06-15T14:32:07-03:00",
    "funcionario_id": 482,
    "data_he": "2026-05-28",
    "regime_trabalho": "mensalista",
    "salario_base_centavos": 330000,
    "modo_divisor": "fixo_220",
    "divisor": 220,
    "valor_hora_centavos": 1500,
    "escala": {
        "escala_id": 17,
        "nome": "44h Seg–Sex+Sáb",
        "tipo": "semanal",
        "carga_semanal_minutos": 2640,
        "vigente_em": "2026-05-28"
    },
    "tipo_he": "he_50",
    "fator_padrao_bps": 5000,
    "fator_aplicado_bps": 5000,
    "override_empresa_aplicado": false,
    "minutos": 90,
    "adicional_noturno": {
        "aplicado": false,
        "bps": 2000,
        "minutos_noturnos": 0,
        "hora_reduzida": false
    },
    "formula": "round(valor_hora_centavos * (10000 + fator_aplicado_bps) * minutos / (10000 * 60))",
    "valor_calculado_centavos": 3375
}
```

> O snapshot é **auto-contido**: o dump de uma HE aprovada contém tudo que a explica (salário, divisor, escala vigente, fator, fórmula, versão do motor). Mudar o salário do funcionário, a escala ou o fator de HE da empresa **amanhã** não altera este registro — é o ponto do ADR-0009. O `versao_engine` permite saber com qual versão do motor de cálculo o número foi produzido, essencial quando a fórmula evoluir em fases futuras.

---

## 5. Workflow de aprovação — máquina de estados

### 5.1 Estados (`StatusHoraExtra`)

```
                          ┌──────────── cancelar ───────────┐
                          │                                 ▼
   ┌──────────┐  lançar  ┌─────────┐  aprovar  ┌──────────┐         ┌───────────┐
   │ rascunho │ ───────► │ lancada │ ────────► │ aprovada │ ──pagar►│   paga    │
   └──────────┘          └─────────┘           └──────────┘         └───────────┘
        │                  ▲   │                  │
        │                  │   │ rejeitar         │ estornar
        └──cancelar──►(cancelada)│                ▼
                               ▼            (volta p/ lancada
                          ┌──────────┐       ou rejeitada)
                          │rejeitada │◄───────────┘
                          └──────────┘
```

`StatusHoraExtra::isFinal()` é `true` para `paga`, `rejeitada` e `cancelada`. `aprovada` **não** é estritamente final: admite `pagar` (avança) e `estornar` (volta), mas é imutável quanto ao **valor** (o snapshot já está congelado — §4).

### 5.2 Tabela de transições

| De → Para                                      | Gatilho / quem                                    | Pré-condições                                                                      | Efeitos                                                                                                                                                                                            |
| ---------------------------------------------- | ------------------------------------------------- | ---------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `rascunho → lancada`                           | **lançar** · gestor (`rh.horas_extras.lancar`)    | `minutos > 0`; funcionário ∈ subárvore do gestor                                   | calcula **preview** (não congela); registra `lancado_por`/`lancado_em`                                                                                                                             |
| `rascunho → cancelada`                         | **cancelar** · quem lançou                        | —                                                                                  | encerra sem efeito; auditoria                                                                                                                                                                      |
| `lancada → aprovada`                           | **aprovar** · gestor (`rh.horas_extras.aprovar`)  | funcionário ∈ subárvore; `aprovado_por ≠ lancado_por` se segregação exigida (§5.4) | **congela** snapshot (§4); grava `valor_calculado_centavos`, `percentual_aplicado_bps`, `valor_hora_base_centavos`, `snapshot_calculo`, `aprovado_por`, `aprovado_em`; resolve `rubrica_id` (§6.3) |
| `lancada → rejeitada`                          | **rejeitar** · gestor (`rh.horas_extras.aprovar`) | funcionário ∈ subárvore                                                            | grava `motivo_rejeicao`; **não** congela snapshot                                                                                                                                                  |
| `lancada → cancelada`                          | **cancelar** · quem lançou                        | enquanto não aprovada                                                              | encerra; auditoria                                                                                                                                                                                 |
| `aprovada → paga`                              | **marcar paga** · `rh.horas_extras.marcar_paga`   | snapshot presente                                                                  | marca pagamento (na Fase 1, marcação gerencial; a folha real é Fase 3)                                                                                                                             |
| `aprovada → lancada` \| `aprovada → rejeitada` | **estornar** · `rh.horas_extras.estornar`         | HE **não** `paga`                                                                  | reverte a aprovação; o snapshot da aprovação anterior fica registrado na auditoria; reaprovar gera **novo** snapshot                                                                               |

Transições **não** listadas são proibidas (ex.: `paga → qualquer`, `rejeitada → aprovada`, `aprovada → cancelada`). Uma tentativa inválida lança exceção de domínio.

### 5.3 Quem lança e quem aprova (liga com a ACL hierárquica)

A autorização tem **dois eixos** combinados (`AND`): a **permissão** (spatie) **e** o **escopo de subárvore** do organograma (mecânica completa em [05](05-organograma-acl-hierarquica.md)).

- **Lança:** o **gestor**, restrito a `funcionario_id ∈ subárvore(gestor_logado)` + permissão `rh.horas_extras.lancar`. Um gestor só lança HE para quem está **abaixo dele** na árvore (`funcionarios.gestor_id`, escopo recursivo via CTE — [05](05-organograma-acl-hierarquica.md)).
- **Aprova:** quem tem `rh.horas_extras.aprovar` **e** o `funcionario_id` da HE ∈ sua subárvore. Aprovar fora da cadeia é negado mesmo com a permissão (o escopo é independente da permissão).

```
pode_lancar(ator, he)  = ator.can('rh.horas_extras.lancar')
                         && he.funcionario_id ∈ subarvore(ator)

pode_aprovar(ator, he) = ator.can('rh.horas_extras.aprovar')
                         && he.funcionario_id ∈ subarvore(ator)
                         && (!exige_segregacao || he.lancado_por != ator.id)
```

### 5.4 Segregação de funções (opcional)

Para evitar que a mesma pessoa lance **e** aprove a própria HE, há uma trava opcional:

```
config('rh.aprovacao.exige_segregacao')   // default false
```

Quando `true`, a transição `lancada → aprovada` exige `aprovado_por_admin_user_id ≠ lancado_por_admin_user_id`. Útil para empresas com controle interno mais rígido; desligado por padrão para não travar equipes pequenas (onde o mesmo gestor faz tudo).

### 5.5 Implementação: uma Action por transição + Policy

Cada transição é uma **Action atômica** (`execute()`), com `match` no enum de status validando o estado de origem, e a autorização na **Policy**:

```php
// HT2ML\Rh\Actions\HorasExtras\AprovarHoraExtra
final class AprovarHoraExtra
{
    public function __construct(
        private readonly CalcularHoraExtra $calculo,
        private readonly ResolverRubricaHe $resolverRubrica,
    ) {}

    public function execute(HoraExtra $he, AdminUser $aprovador): HoraExtra
    {
        // 1. Guarda de estado (máquina de estados).
        $he->status === StatusHoraExtra::LANCADA
            || throw new TransicaoHoraExtraInvalidaException($he->status, StatusHoraExtra::APROVADA);

        return DB::transaction(function () use ($he, $aprovador) {
            // 2. Recalcula com salário/escala vigentes na DATA da HE e congela o snapshot.
            $resultado = $this->calculo->paraData($he->funcionario, $he->data, $he->tipo, $he->minutos);

            $he->fill([
                'status'                     => StatusHoraExtra::APROVADA,
                'valor_calculado_centavos'   => $resultado->valorCentavos,
                'valor_hora_base_centavos'   => $resultado->valorHoraCentavos,
                'percentual_aplicado_bps'    => $resultado->fatorBps,
                'snapshot_calculo'           => $resultado->snapshot,   // JSONB imutável (ADR-0009)
                'rubrica_id'                 => $this->resolverRubrica->paraTipo($he->tipo)?->id,
                'aprovado_por_admin_user_id' => $aprovador->id,
                'aprovado_em'                => now(),
            ])->save();

            return $he;
        });
    }
}
```

A autorização (permissão + subárvore + segregação) é checada na `HoraExtraPolicy::aprovar()` **antes** da Action ser chamada (no componente Livewire). A Action assume já-autorizado e cuida só do invariante de estado + cálculo + snapshot.

```php
// HT2ML\Rh\Policies\HoraExtraPolicy
public function aprovar(AdminUser $ator, HoraExtra $he): bool
{
    return $ator->can('rh.horas_extras.aprovar')
        && $this->escopo->contemFuncionario($ator, $he->funcionario_id)   // subárvore (doc 05)
        && (! config('rh.aprovacao.exige_segregacao')
            || $he->lancado_por_admin_user_id !== $ator->id);
}
```

### 5.6 Permissões da HE

| Permissão                     | Habilita                                                                                                                                  |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `rh.horas_extras.lancar`      | criar/editar enquanto `rascunho`/`lancada`; lançar (rascunho→lancada)                                                                     |
| `rh.horas_extras.aprovar`     | aprovar (lancada→aprovada) e rejeitar (lancada→rejeitada)                                                                                 |
| `rh.horas_extras.estornar`    | estornar (aprovada→lancada/rejeitada), exceto se `paga`                                                                                   |
| `rh.horas_extras.marcar_paga` | marcar paga (aprovada→paga)                                                                                                               |
| `rh.horas_extras.ver_valores` | ver as colunas de valor (`valor_calculado_centavos`, base/hora) e o `snapshot_calculo` — separa "ver que houve HE" de "ver quanto custou" |

> `ver_valores` é uma permissão de **confidencialidade**: um supervisor pode ver que um subordinado fez 2h extras sem necessariamente ver o valor em reais (que depende do salário, dado sensível). Sem ela, a UI oculta as colunas monetárias e o snapshot. Cancelar enquanto `rascunho`/`lancada` cabe a quem lançou (coberto por `lancar`); não há lixeira em `horas_extras` — o ciclo é por **status** (`cancelada`), não soft-delete ([01 §3 D1](01-modelo-de-dominio.md): tabela **sem `deleted_at`**).

---

## 6. Fundação de folha

A Fase 1 constrói a **estrutura** que a folha futura consome. Reforçando a fronteira do §0: aqui há **catálogo + parâmetros + ligação**, não **apuração**.

### 6.1 Catálogo `rubricas` (proventos/descontos/informativas)

`rubricas` ([01 §3 A9](01-modelo-de-dominio.md)) é o catálogo de **verbas** de folha, por empresa. Cada rubrica declara sua **natureza** e suas **incidências** — as flags que a apuração futura lerá para montar as bases de cálculo:

| Coluna                                        | Papel                                                                                           |
| --------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `codigo`                                      | chave estável (`salario`, `he_50`, `desc_inss`…), única por empresa                             |
| `natureza` (`NaturezaRubrica`)                | **provento** (soma), **desconto** (subtrai), **informativa** (não entra no líquido — ex.: FGTS) |
| `incide_inss` / `incide_fgts` / `incide_irrf` | a rubrica compõe a base daquele encargo?                                                        |
| `codigo_esocial`                              | mapeia para a **tabela 03 (rubricas) do eSocial** — insumo da Fase 4                            |
| `referencia_he_tipo`                          | liga um `TipoHoraExtra` a esta rubrica (a ponte HE→rubrica — §6.3)                              |

Seeds padrão ([01 §5](01-modelo-de-dominio.md)): Salário Base (provento, incide tudo), HE 50% (`referencia_he_tipo=he_50`), HE 100%, Adicional Noturno, DSR sobre HE, INSS (desconto), IRRF (desconto), FGTS (informativa), Vale-Transporte (desconto), Salário-Família (provento).

### 6.2 Referência `tabelas_legais` (parâmetros por vigência)

`tabelas_legais` ([01 §"Referência de apoio à folha"](01-modelo-de-dominio.md)) guarda os **parâmetros nacionais** que mudam por **competência**: faixas/alíquotas de **INSS**, **IRRF** e **salário-família**. Modelagem: referência versionada por `vigencia_inicio`/`vigencia_fim` + `tipo` ∈ {`inss`, `irrf`, `salario_familia`} + **payload JSONB** com as faixas.

Exemplo do payload (faixas progressivas de INSS, ilustrativo):

```json
{
    "tipo": "inss",
    "vigencia_inicio": "2026-01-01",
    "vigencia_fim": null,
    "faixas": [
        { "ate_centavos": 151200, "aliquota_bps": 750 },
        { "ate_centavos": 282560, "aliquota_bps": 900 },
        { "ate_centavos": 423880, "aliquota_bps": 1200 },
        { "ate_centavos": 825410, "aliquota_bps": 1400 }
    ],
    "teto_centavos": 825410
}
```

Nasce como **seed do pacote** (competência vigente) e é **atualizável por competência** — quando o governo publica novas faixas, adiciona-se uma nova linha com `vigencia_inicio` na competência seguinte (e encerra-se a anterior com `vigencia_fim`). A resolução "qual tabela vale na competência C" é análoga à da escala vigente (§1.5): a linha cujo intervalo de vigência contém C.

> Os valores acima são **ilustrativos** para mostrar a forma do payload. Os números reais entram no seed validados pela competência-alvo. A Fase 1 **carrega e consulta** essas tabelas; **não as aplica** a nenhum cálculo de folha (isso é Fase 3).

### 6.3 Como a HE aprovada se conecta à folha

A ponte é a coluna `horas_extras.rubrica_id` (FK→`rubricas`, `nullOnDelete`). Na aprovação (§5.5), a Action resolve a rubrica de HE correta a partir do `tipo` da HE, via o mapeamento `rubricas.referencia_he_tipo`:

```
resolver_rubrica(he) = rubricas
    .where('empresa_id', he.empresa_id)
    .where('referencia_he_tipo', he.tipo->value)   // ex.: 'he_50'
    .where('ativo', true)
    .first()
```

Assim, uma HE 50% aprovada aponta para a rubrica "Hora Extra 50%". A **apuração futura (Fase 3)** então:

1. somará, por funcionário e competência, os `valor_calculado_centavos` das HE aprovadas **agrupados por rubrica**;
2. usará `rubricas.incide_inss/fgts/irrf` para compor as bases;
3. aplicará as faixas de `tabelas_legais` vigentes na competência;
4. gravará o resultado como um **novo snapshot imutável por competência** (mesmo padrão ADR-0009).

Nada disso roda na Fase 1 — mas o **contrato** (rubrica certa, valor congelado, incidências declaradas) já está pronto.

### 6.4 É fundação vs NÃO é fundação (a fronteira, item a item)

| **É fundação (Fase 1 entrega — estrutura)**                          | **NÃO é fundação (Fase 3+ — apuração)**                      |
| -------------------------------------------------------------------- | ------------------------------------------------------------ |
| Catálogo `rubricas` com natureza + incidências + `codigo_esocial`    | Somar rubricas por competência para montar o demonstrativo   |
| `tabelas_legais` carregadas por vigência (INSS/IRRF/salário-família) | **Aplicar** as faixas: calcular INSS/FGTS/IRRF de fato       |
| HE aprovada → `rubrica_id` resolvido (a ponte)                       | Holerite/contracheque PDF                                    |
| `valor_calculado_centavos` congelado por HE                          | 13º salário, recibo de férias, médias                        |
| Flags de incidência declaradas (`incide_*`)                          | Dedução de dependentes no IRRF, salário-família efetivo      |
| Snapshot imutável (ADR-0009) reproduzível                            | Fechamento de competência, eSocial S-1200/S-1210 transmitido |

---

## 7. Request fim-a-fim — gestor aprova uma HE

Sequência completa de uma aprovação, juntando ACL, cálculo, snapshot e auditoria:

```
1. Gestor abre a fila de aprovação (Livewire HoraExtraTable, escopada à subárvore).
   └─ Listagem já filtrada: só HE de funcionarios ∈ subarvore(gestor) [doc 05, CTE recursiva].

2. Gestor clica "Aprovar" numa HE em status `lancada`.
   └─ Componente chama HoraExtraPolicy::aprovar($gestor, $he):
        • $gestor->can('rh.horas_extras.aprovar')              ✔ permissão (spatie)
        • escopo->contemFuncionario($gestor, $he->funcionario_id)  ✔ subárvore (doc 05)
        • segregação: !exige_segregacao || lancado_por != gestor   ✔ (§5.4)
      → autorizado.  (Falha em qualquer eixo ⇒ AuthorizationException ⇒ 403.)

3. AprovarHoraExtra::execute($he, $gestor)  [dentro de DB::transaction]:
   a. Guarda de estado: $he->status === LANCADA, senão TransicaoHoraExtraInvalidaException. (§5.2)
   b. Resolve a ESCALA vigente na DATA da HE (não a atual). (§1.5)
   c. ValorHora::calcular($funcionario, $escala) → valor_hora_centavos. (§2)
   d. Resolve fator efetivo: override por empresa ?? TipoHoraExtra::fatorPadraoBps(). (§3.3)
   e. Aplica a fórmula → valor_calculado_centavos. (§3.2) [+ adicional noturno, se houver §3.4]
   f. Monta snapshot_calculo (JSONB) com salário/divisor/escala/fator/fórmula/versao_engine. (§4.3)
   g. Resolve rubrica_id via referencia_he_tipo. (§6.3)
   h. Grava: status=aprovada, valores, snapshot, rubrica_id, aprovado_por, aprovado_em. (§5.5)

4. Auditoria (trait Auditavel / spatie-activitylog) registra a mudança de status e os campos
   alterados — append-only, automático no save. (CLAUDE.md §11; [01 §0])
   └─ Atenção LGPD: o snapshot guarda salário (sensível) — visível só sob rh.horas_extras.ver_valores.

5. UI: toast "Hora extra aprovada." A HE sai da fila de pendências; o valor congelado passa a
   compor a base de custo gerencial e fica pronto como insumo de folha (Fase 3). (§0, §6.3)
```

Imutabilidade garantida: a partir do passo 3f, `snapshot_calculo` e `valor_calculado_centavos` **não mudam** — nem por reajuste salarial, nem por mudança de escala, nem por override de fator posterior (ADR-0009). Corrigir uma HE aprovada errada é **estornar** (volta a `lancada`) e reaprovar, gerando um **novo** snapshot (§5.2).

---

## 8. Configuração

Todas as chaves vivem em `config/rh.php` do pacote (`packages/extensao-rh/config/rh.php`), com defaults seguros e sobrepujáveis por `.env`/publish.

### 8.1 `config('rh.calculo.*')` — cálculo de HE e valor-hora

| Chave                              | Default      | Significado                                                                                       |
| ---------------------------------- | ------------ | ------------------------------------------------------------------------------------------------- |
| `rh.calculo.divisor_horas_mensais` | `220`        | divisor de horas mensais (praxe CLT 44h) quando a escala não define o seu                         |
| `rh.calculo.modo_divisor`          | `'fixo_220'` | `fixo_220` (usa o divisor configurado) \| `derivado_da_carga` (calcula da carga da escala) — §2.3 |
| `rh.calculo.adicional_noturno_bps` | `2000`       | adicional noturno em bps (+20%, mínimo legal) — §3.4                                              |
| `rh.calculo.hora_noturna_reduzida` | `false`      | conta a hora noturna como 52'30" (esticando os minutos) — §3.4                                    |
| `rh.calculo.versao_engine`         | `'1.0.0'`    | versão do motor de cálculo, gravada em cada `snapshot_calculo` — §4.3                             |

> A **precedência do divisor** (§2.3) é resolvida em código (`escalas.horas_mensais_divisor` > config > 220); a config é o **2º nível**. Os **fatores de HE** vêm do enum `TipoHoraExtra::fatorPadraoBps()` (não de config), sobrepujáveis pelo **catálogo de override por empresa** (§3.3) — não por `.env`, para manter type-safety + multi-tenancy.

### 8.2 `config('rh.aprovacao.*')` — workflow

| Chave                           | Default | Significado                                                                           |
| ------------------------------- | ------- | ------------------------------------------------------------------------------------- |
| `rh.aprovacao.exige_segregacao` | `false` | se `true`, quem aprova não pode ser quem lançou (`aprovado_por ≠ lancado_por`) — §5.4 |

> O **escopo de subárvore** (quem pode lançar/aprovar para quem) **não** é config — é estrutural, vem do organograma (`funcionarios.gestor_id`) e da ACL hierárquica ([05](05-organograma-acl-hierarquica.md)). Config governa apenas a **política de segregação**, que é uma decisão de controle interno por instalação.

### 8.3 Esboço do arquivo

```php
// packages/extensao-rh/config/rh.php  (trecho)
return [
    'calculo' => [
        'divisor_horas_mensais' => env('RH_DIVISOR_HORAS_MENSAIS', 220),
        'modo_divisor'          => env('RH_MODO_DIVISOR', 'fixo_220'),
        'adicional_noturno_bps' => env('RH_ADICIONAL_NOTURNO_BPS', 2000),
        'hora_noturna_reduzida' => env('RH_HORA_NOTURNA_REDUZIDA', false),
        'versao_engine'         => '1.0.0',
    ],
    'aprovacao' => [
        'exige_segregacao' => env('RH_APROVACAO_EXIGE_SEGREGACAO', false),
    ],
    // 'permissoes' => [...],  // rh.* mescladas no core (ver [02 §B1])
];
```

---

## 9. Síntese

- **Jornada** = `escalas` (cabeçalho + divisor + carga cache) × `escala_dias` (1 linha por dia×turno, ISO 1=segunda, intervalo = lacuna, `saida<entrada` cruz a meia-noite) × `escala_funcionario` (vigência SCD, uma aberta, escala da **data** para cálculos passados). Carga semanal/diária **derivada** dos turnos, conferida na escrita. Cobre 44h, 40h, 12×36, estágio 6h, parcial 30h.
- **Valor-hora** = mensalista `round(salario/divisor)`; horista = salário. Divisor por precedência `escala > config > 220`; `modo_divisor` recomendado `fixo_220`. Helper `ValorHora::calcular(Funcionario, Escala): int`. Tudo em centavos inteiros, `round` só na divisão final (ADR-0014).
- **Hora extra** = `round(valor_hora × (10000 + fator_bps)/10000 × minutos/60)`. Fatores em bps inteiros no enum `TipoHoraExtra` (`he_50`=5000, `he_100`=10000), **sobrepujáveis por catálogo de override por empresa** (type-safety + flexibilidade). Adicional noturno 22h–5h (`+2000 bps` default), hora reduzida 52'30" opcional (aproximação gerencial na Fase 1).
- **Snapshot imutável (ADR-0009)** — na aprovação congela `valor_calculado_centavos` + `snapshot_calculo` JSONB (salário, divisor, valor-hora, escala, fator, fórmula, `versao_engine`). Preview "ao vivo" volátil antes; congelado depois; nunca recalculado.
- **Workflow** — `rascunho→lancada→(aprovada|rejeitada)`, `aprovada→paga`, `aprovada→(estorno)→lancada/rejeitada`, `rascunho→cancelada`. Uma **Action por transição** (guarda de estado por `match`) + **Policy** (permissão **e** subárvore do organograma + segregação opcional). Permissões `rh.horas_extras.{lancar,aprovar,estornar,marcar_paga,ver_valores}`.
- **Fundação de folha** — `rubricas` (natureza + incidências + `codigo_esocial` + `referencia_he_tipo`) e `tabelas_legais` (faixas por vigência, payload JSONB). HE aprovada → `rubrica_id` resolvido (a ponte). **É** estrutura/contrato; **NÃO é** apuração — somar rubricas, aplicar INSS/FGTS/IRRF, holerite, 13º/férias, eSocial transmitido são **Fase 3+** ([09](09-roadmap-fases.md)).
