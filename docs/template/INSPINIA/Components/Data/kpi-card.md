# KPI Card

**Categoria:** Data
**Origem Inspinia:** `resources/views/dashboard/analytics.blade.php` + `resources/views/dashboard/ecommerce.blade.php` (extraído)
**Plugins JS:** Nenhum (exceto ApexCharts para sparkline opcional)
**Plugins CSS:** Tailwind + classes do Inspinia `.card`

---

## Descrição

Card compacto exibindo um KPI (Key Performance Indicator): **label**, **valor principal**, **ícone** à direita (com bg colorido), opcional **trend** (± percentual vs. período anterior) e opcional **sparkline** (mini-gráfico). Usado no **topo do Dashboard 14.2** em grid de 4 colunas.

---

## Código de referência (extraído do analytics.blade.php do Inspinia)

```html
<div class="card">
    <div class="card-body">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-default-400 mb-1 text-sm">Total Revenue</p>
                <h3 class="text-2xl font-bold">$48,275</h3>
                <p class="text-success mt-2 text-xs">
                    <i class="iconify tabler--trending-up"></i>
                    +12.5% vs último mês
                </p>
            </div>
            <div class="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                <i class="iconify tabler--currency-dollar text-2xl"></i>
            </div>
        </div>
    </div>
</div>
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.kpi-card>`
**Arquivo:** `resources/views/components/admin/kpi-card.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop         | Tipo          | Obrigatório | Default                  | Descrição                                     |
| ------------ | ------------- | :---------: | ------------------------ | --------------------------------------------- |
| `label`      | `string`      |     ✅      | —                        | Título do KPI                                 |
| `value`      | `string\|int` |     ✅      | —                        | Valor principal (pré-formatado)               |
| `icon`       | `string`      |     ✅      | —                        | Ícone Iconify                                 |
| `color`      | `string`      |     ❌      | `'primary'`              | primary, success, warning, danger, info       |
| `trend`      | `?float`      |     ❌      | `null`                   | Percentual de variação (positivo ou negativo) |
| `trendLabel` | `string`      |     ❌      | `'vs. período anterior'` | Texto após o trend                            |
| `href`       | `?string`     |     ❌      | `null`                   | Se fornecido, card clicável                   |

### Código

```blade
{{-- resources/views/components/admin/kpi-card.blade.php --}}
@props ([
    'label',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'trendLabel' => 'vs. período anterior',
    'href' => null,
])

@php
    $trendPositive = $trend !== null && $trend >= 0;
    $trendColor = $trend === null ? null : ($trendPositive ? 'success' : 'danger');
    $trendIcon = $trendPositive ? 'tabler--trending-up' : 'tabler--trending-down';
    $trendSign = $trendPositive ? '+' : '';
@endphp

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'card transition',
        'hover:shadow-md cursor-pointer' => $href,
    ]) }}
>
    <div class="card-body">
        <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-default-400 text-sm mb-1 truncate">{{ $label }}</p>
                <h3 class="text-2xl font-bold truncate">{{ $value }}</h3>

                @if ($trend !== null)
                    <p class="text-{{ $trendColor }} text-xs mt-2 flex items-center gap-1">
                        <i class="iconify {{ $trendIcon }}"></i>
                        {{ $trendSign }}{{ number_format($trend, 1, ',', '.') }}% {{ $trendLabel }}
                    </p>
                @endif
            </div>

            <div
                class="bg-{{ $color }}/10 text-{{ $color }} size-12 rounded-full flex items-center justify-center shrink-0 ms-3"
            >
                <i class="iconify {{ $icon }} text-2xl"></i>
            </div>
        </div>
    </div>
</{{ $tag }}>
```

---

## Exemplos de Uso

### Real (Dashboard 14.2 — 4 KPIs topo)

```blade
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <x-admin.kpi-card
        label="Contratos Ativos"
        :value="$kpis->contratosAtivos"
        icon="tabler--file-text"
        color="primary"
        :trend="$kpis->trendContratos"
        :href="route('admin.contratos.index')"
    />

    <x-admin.kpi-card
        label="Formandos Aderidos"
        :value="$kpis->formandosAderidos"
        icon="tabler--users"
        color="success"
        :trend="$kpis->trendFormandos"
        :href="route('admin.formandos.index')"
    />

    <x-admin.kpi-card
        label="Receita a Receber"
        :value="MoneyHelper::format($kpis->receitaPendenteCentavos)"
        icon="tabler--cash"
        color="warning"
        :href="route('admin.financeiro.parcelas.index', ['status' => 'pendente'])"
    />

    <x-admin.kpi-card
        label="Inadimplência"
        :value="number_format($kpis->inadimplenciaPct, 1, ',', '.') . '%'"
        icon="tabler--alert-triangle"
        color="danger"
        :trend="-$kpis->trendInadimplencia"
        trend-label="vs. último mês"
        :href="route('admin.financeiro.parcelas.index', ['status' => 'vencido'])"
    />
</div>
```

### Real (Totalizadores da ficha 14.12 Tab 5)

```blade
<div class="grid grid-cols-4 gap-4 mb-4">
    <x-admin.kpi-card
        label="Total Geral"
        :value="MoneyHelper::format($totais->geral)"
        icon="tabler--cash"
        color="primary"
    />
    <x-admin.kpi-card
        label="Pago"
        :value="MoneyHelper::format($totais->pago)"
        icon="tabler--circle-check"
        color="success"
    />
    <x-admin.kpi-card
        label="Pendente"
        :value="MoneyHelper::format($totais->pendente)"
        icon="tabler--clock"
        color="warning"
    />
    <x-admin.kpi-card
        label="Vencido"
        :value="MoneyHelper::format($totais->vencido)"
        icon="tabler--alert-triangle"
        color="danger"
    />
</div>
```

### Real (Relatório de Parcelas — KPIs filtrados 14.13)

```blade
<div class="grid grid-cols-5 gap-4 mb-4">
    <x-admin.kpi-card label="Total de Parcelas" :value="$stats->total" icon="tabler--list-numbers" color="primary" />
    <x-admin.kpi-card
        label="Valor Total"
        :value="MoneyHelper::format($stats->valorTotal)"
        icon="tabler--cash"
        color="info"
    />
    <x-admin.kpi-card
        label="Recebido"
        :value="MoneyHelper::format($stats->recebido)"
        icon="tabler--circle-check"
        color="success"
    />
    <x-admin.kpi-card
        label="A Receber"
        :value="MoneyHelper::format($stats->aReceber)"
        icon="tabler--clock"
        color="warning"
    />
    <x-admin.kpi-card
        label="Vencidas"
        :value="MoneyHelper::format($stats->vencidas)"
        icon="tabler--alert-triangle"
        color="danger"
    />
</div>
```

---

## Quando Usar ✅

- Dashboard com métricas top-level (14.2)
- Topo de tabs financeiras (14.12 Tab 5, 14.13)
- Relatórios agregados (14.17)
- Cards de stats em qualquer view que agregue dados

## Quando NÃO Usar ❌

- Gráficos reais → usar `<x-admin.chart-*>`
- Texto longo → usar `<x-shared.card>`
- Informações hierárquicas → usar `<x-shared.list-group>`

---

## Mapeamento no PRD

| Tela                    | Seção PRD | Qtd KPIs | KPIs                                                               |
| ----------------------- | --------- | :------: | ------------------------------------------------------------------ |
| Dashboard               | 14.2      |    4     | Contratos Ativos, Formandos Aderidos, Receita Total, Inadimplência |
| Formandos ficha (Tab 5) | 14.12     |    4     | Total Geral, Pago, Pendente, Vencido                               |
| Parcelas (topo)         | 14.13     |    5     | Total, Valor Total, Recebido, A Receber, Vencidas                  |
| Relatórios              | 14.17     | variável | Por relatório                                                      |

---

## Classificação

| Critério         | Valor            |
| ---------------- | ---------------- |
| **Vai usar**     | 🟢 Sim (crítico) |
| **Prioridade**   | P1 (Onda 2)      |
| **Complexidade** | Simples          |
| **Status**       | 🟢 Concluído     |

---

## Código Final Blade

**Arquivo:** `resources/views/components/admin/kpi-card.blade.php`
**Preview:** `resources/views/admin/dev/components/kpi-card.blade.php`

### API final consolidada

| Prop         | Tipo          | Default                | Observação                                                   |
| ------------ | ------------- | ---------------------- | ------------------------------------------------------------ |
| `label`      | `string`      | —                      | Texto superior do KPI                                        |
| `value`      | `string\|int` | —                      | Valor principal já formatado                                 |
| `icon`       | `string`      | —                      | Ícone Tabler/Iconify                                         |
| `color`      | `string`      | `primary`              | `primary`, `success`, `warning`, `danger`, `info`, `default` |
| `trend`      | `?float`      | `null`                 | Percentual de variação                                       |
| `trendLabel` | `string`      | `vs. período anterior` | Texto auxiliar do trend                                      |
| `href`       | `?string`     | `null`                 | Torna o card clicável                                        |

### Slots finais

| Slot    | Uso                                          |
| ------- | -------------------------------------------- |
| default | Complemento opcional abaixo do KPI principal |

### Código

```blade
<x-admin.kpi-card label="Formandos Aderidos" value="1.284" icon="tabler--users" color="success" :trend="8.2" href="#!">
    Meta mensal já em 72% do planejado.
</x-admin.kpi-card>
```

---

## Notas de Adaptação

1. **Valores pré-formatados**: passar `MoneyHelper::format(...)` ou `number_format(...)` direto; o componente não formata dinheiro nem porcentagem
2. **Mapa explícito de cores** no Blade final, sem depender de classes dinâmicas/safelist ampla
3. **Clicável opcional**: quando `href` existe, o card vira link com hover suave e focus ring
4. **Trend positivo/negativo** continua controlado pelo valor recebido; para casos como inadimplência em queda, seguir invertendo manualmente o sinal na chamada
5. **Ícone Tabler/Iconify** permanece obrigatório para manter leitura rápida dos KPIs do dashboard
6. **Conteúdo extra** no slot final cobre observações curtas, sem abrir uma família nova de metric/widget
7. **Sparkline** segue no parking lot e não faz parte do contrato oficial atual
