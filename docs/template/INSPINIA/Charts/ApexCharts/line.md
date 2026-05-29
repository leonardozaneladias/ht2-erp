# ApexCharts — Line (Dual Series)

**Categoria:** Chart
**Origem Inspinia:** `resources/views/charts/apex/line.blade.php`
**Plugins JS:** ApexCharts 5.3.5
**Uso típico:** Dashboard — "Receita x Despesa" (line dual)

---

## Descrição

Gráfico de linhas com **múltiplas séries**. Exemplo típico: 2 séries no mesmo chart, receita recebida vs. despesa ao longo de 12 meses.

---

## Configuração mínima

```js
const options = {
    chart: {
        type: 'line',
        height: 320,
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    series: [
        {
            name: 'Receita Recebida',
            data: [12000, 14000, 13500, 15000, 16000, 15500, 17000, 18000, 17500, 19000, 20000, 21000],
        },
        { name: 'Despesa', data: [2000, 2500, 2200, 3000, 2800, 3200, 3500, 3000, 3400, 3100, 2800, 2900] },
    ],
    xaxis: {
        categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
    },
    yaxis: {
        labels: {
            formatter: (val) => 'R$ ' + (val / 1000).toFixed(0) + 'k',
        },
    },
    colors: ['#10B981', '#EF4444'], // verde, vermelho
    legend: { position: 'top' },
    tooltip: {
        y: { formatter: (val) => 'R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(val) },
    },
};

new ApexCharts(document.querySelector('#chart-line-dual'), options).render();
```

---

## Exemplo de Uso (Dashboard)

```blade
<x-admin.chart-card title="Receita x Despesa (12 meses)" chart-id="receita-despesa" :height="320">
    <livewire:admin.dashboard.grafico-receita-despesa chart-id="receita-despesa" />
</x-admin.chart-card>
```

```php
class GraficoReceitaDespesa extends Component
{
    public string $chartId;

    public function render()
    {
        $meses = collect(range(11, 0))->map(fn($i) => now()->subMonths($i));

        $receita = $meses->map(fn($m) => Lancamento::receitas()
            ->whereYear('pago_em', $m->year)
            ->whereMonth('pago_em', $m->month)
            ->sum('valor_centavos') / 100
        )->toArray();

        $despesa = $meses->map(fn($m) => Lancamento::despesas()
            ->whereYear('pago_em', $m->year)
            ->whereMonth('pago_em', $m->month)
            ->sum('valor_centavos') / 100
        )->toArray();

        $this->dispatch('chart-update',
            chartId: $this->chartId,
            type: 'line',
            data: [
                'series' => [
                    ['name' => 'Receita Recebida', 'data' => $receita],
                    ['name' => 'Despesa', 'data' => $despesa],
                ],
                'categories' => $meses->map->format('M/y')->toArray(),
                'colors' => ['#10B981', '#EF4444'],
            ]
        );

        return view('livewire.admin.dashboard.grafico-receita-despesa');
    }
}
```

---

## Casos de uso típicos

| Contexto    | Uso                                               |
| ----------- | ------------------------------------------------- |
| Dashboard   | **"Receita x Despesa" ao longo do tempo**         |
| Relatórios  | Evolução de métricas no tempo                     |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Dual series** — array de 2+ objetos em `series`
2. **Formatter `pt-BR`** no yaxis e tooltip — valores em R$
3. **Cores semânticas:** verde (bom — receita), vermelho (ruim — despesa)
4. **`curve: 'smooth'`** para linhas curvas bonitas
5. **`zoom: false`** — desabilitar zoom (usuário não precisa, mantém simples)
6. **Legend no topo** — identifica cada série

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/admin/chart-line.blade.php`
- `resources/js/admin/charts.js`
  **Preview:** `resources/views/admin/dev/components/chart-line.blade.php`

### API final consolidada

| Prop         | Tipo      | Default                                 |
| ------------ | --------- | --------------------------------------- |
| `title`      | `string`  | —                                       |
| `subtitle`   | `?string` | `null`                                  |
| `chartId`    | `?string` | auto                                    |
| `height`     | `int`     | `320`                                   |
| `series`     | `array`   | `[]`                                    |
| `categories` | `array`   | `[]`                                    |
| `colors`     | `array`   | `['--color-success', '--color-danger']` |
| `options`    | `array`   | `[]`                                    |

### Observações de implementação

- o wrapper final centraliza linhas múltiplas sobre a mesma bridge dos outros charts
- o contrato Blade ficou alinhado a `chart-card`, com `headerActions` e texto auxiliar opcionais
- o preview cobre o caso canônico de receita x despesa
