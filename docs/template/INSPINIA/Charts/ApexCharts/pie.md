# ApexCharts — Pie / Donut

**Categoria:** Chart
**Origem Inspinia:** `resources/views/charts/apex/pie.blade.php`
**Plugins JS:** ApexCharts 5.3.5
**Uso no ArtFinal:** 14.17 Relatórios — distribuição por modalidade de pagamento

---

## Descrição

Gráfico de pizza ou donut (pizza com buraco no centro). Usado para mostrar **proporção** entre categorias (percentual de cada modalidade, status de parcelas, etc.).

---

## Configuração mínima

```js
const options = {
    chart: {
        type: 'donut', // ou 'pie'
        height: 320,
    },
    series: [44, 55, 13],
    labels: ['Boleto', 'Cartão', 'PIX'],
    colors: ['#5B73E8', '#10B981', '#F59E0B'],
    legend: { position: 'bottom' },
    dataLabels: {
        enabled: true,
        formatter: (val) => val.toFixed(1) + '%',
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    },
                },
            },
        },
    },
};

new ApexCharts(document.querySelector('#chart-pie'), options).render();
```

---

## Exemplo de Uso (Relatório 14.17)

```blade
<x-admin.chart-card title="Distribuição por Modalidade" chart-id="dist-modalidade" :height="320">
    <livewire:admin.relatorios.grafico-distribuicao-modalidade chart-id="dist-modalidade" />
</x-admin.chart-card>
```

```php
class GraficoDistribuicaoModalidade extends Component
{
    public string $chartId;

    public function render()
    {
        $dados = Parcela::query()
            ->selectRaw('modalidade, COUNT(*) as total')
            ->groupBy('modalidade')
            ->pluck('total', 'modalidade');

        $this->dispatch('chart-update',
            chartId: $this->chartId,
            type: 'donut',
            data: [
                'series' => $dados->values()->toArray(),
                'labels' => $dados->keys()->map(fn($m) => ModalidadePagamento::from($m)->label())->toArray(),
                'colors' => ['#5B73E8', '#10B981', '#F59E0B', '#8B5CF6'],
            ]
        );

        return view('livewire.admin.relatorios.grafico-distribuicao-modalidade');
    }
}
```

---

## Mapeamento no PRD

| Tela               | Uso                                      |
| ------------------ | ---------------------------------------- |
| 14.17 Relatórios   | Distribuição de modalidades de pagamento |
| 14.17 Relatórios   | Distribuição de formandos por curso      |
| Dashboard (futuro) | Distribuição de parcelas por status      |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P3 (Onda 4)  |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Donut preferido sobre Pie** — mais moderno e permite "Total" no centro
2. **Labels com `formatter`** — mostra percentual
3. **Cores consistentes** em todos os charts — definir paleta global
4. **Series é array simples de números** — diferente de bar/line que usa objetos `{name, data}`
5. **Legend bottom** em pie/donut — não ocupa lateral do gráfico

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/admin/chart-pie.blade.php`
- `resources/js/admin/charts.js`
  **Preview:** `resources/views/admin/dev/components/chart-pie.blade.php`

### API final consolidada

| Prop       | Tipo      | Default                                                                     |
| ---------- | --------- | --------------------------------------------------------------------------- |
| `title`    | `string`  | —                                                                           |
| `subtitle` | `?string` | `null`                                                                      |
| `chartId`  | `?string` | auto                                                                        |
| `height`   | `int`     | `320`                                                                       |
| `series`   | `array`   | `[]`                                                                        |
| `labels`   | `array`   | `[]`                                                                        |
| `colors`   | `array`   | `['--color-primary', '--color-success', '--color-warning', '--color-info']` |
| `type`     | `string`  | `donut`                                                                     |
| `options`  | `array`   | `[]`                                                                        |

### Observações de implementação

- o wrapper final aceita `type="donut"` e `type="pie"` sem mudar a API principal
- a bridge ativa total central apenas para donut
- o preview cobre distribuição por modalidade e status das parcelas
