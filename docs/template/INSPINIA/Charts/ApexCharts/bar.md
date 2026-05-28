# ApexCharts — Bar

**Categoria:** Chart
**Origem Inspinia:** `resources/views/charts/apex/bar.blade.php`
**Plugins JS:** ApexCharts 5.3.5
**Uso no ArtFinal:** Dashboard 14.2 — "Adesões por Mês"

---

## Descrição

Gráfico de barras horizontais (ou verticais — ver `column.md`). Bar tradicionalmente é horizontal em ApexCharts; **bar vertical = column**. Usaremos **bar** para comparação entre categorias (ex: contratos x quantidade de adesões) e **column** para séries temporais (adesões por mês).

---

## Configuração mínima ApexCharts

```js
const options = {
    chart: {
        type: 'bar',
        height: 320,
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true, // horizontal
            borderRadius: 4,
            dataLabels: { position: 'top' },
        },
    },
    series: [
        {
            name: 'Adesões',
            data: [120, 85, 60, 40, 30],
        },
    ],
    xaxis: {
        categories: ['UNESP 2026', 'USP 2026', 'UNICAMP 2026', 'FMU 2025', 'FMU 2026'],
    },
    colors: ['#5B73E8'],
    dataLabels: { enabled: true, offsetX: 20 },
};

new ApexCharts(document.querySelector('#chart-bar'), options).render();
```

---

## Exemplo de Uso (ArtFinal)

### Dashboard — Top contratos por adesões

```blade
<x-admin.chart-card title="Top Contratos por Adesões" chart-id="top-contratos" :height="320">
    <livewire:admin.dashboard.grafico-top-contratos chart-id="top-contratos" />
</x-admin.chart-card>
```

```php
// app/Livewire/Admin/Dashboard/GraficoTopContratos.php
class GraficoTopContratos extends Component
{
    public string $chartId;

    public function render()
    {
        $contratos = Contrato::withCount('adesoesAtivas')
            ->orderByDesc('adesoes_ativas_count')
            ->limit(10)
            ->get();

        $this->dispatch('chart-update',
            chartId: $this->chartId,
            type: 'bar',
            data: [
                'series' => [['name' => 'Adesões', 'data' => $contratos->pluck('adesoes_ativas_count')->toArray()]],
                'categories' => $contratos->map(fn($c) => $c->codigo_turma)->toArray(),
            ]
        );

        return view('livewire.admin.dashboard.grafico-top-contratos');
    }
}
```

---

## Mapeamento no PRD

| Tela             | Uso                                                         |
| ---------------- | ----------------------------------------------------------- |
| 14.2 Dashboard   | "Adesões por Mês" — **na verdade é column (ver column.md)** |
| 14.17 Relatórios | Top contratos por adesões (opcional)                        |

---

## Classificação

| Critério         | Valor                       |
| ---------------- | --------------------------- |
| **Vai usar**     | 🟢 Sim                      |
| **Prioridade**   | P3 (Onda 4)                 |
| **Complexidade** | Trivial (config ApexCharts) |
| **Status**       | 🟢 Concluído                |

---

## Notas de Adaptação

1. **Bar vs Column:** bar é horizontal (`horizontal: true`), column é vertical. O Inspinia separa em `bar.blade.php` e `column.blade.php` mas o tipo JS é o mesmo (`type: 'bar'` + `plotOptions.bar.horizontal`)
2. **Data labels** dentro das barras ou ao lado — configurar em `dataLabels`
3. **Cor única** via `colors: ['#5B73E8']`
4. **Grouped/Stacked** (14 variantes do Inspinia) — parking lot por enquanto

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/admin/chart-bar.blade.php`
- `resources/js/admin/charts.js`
  **Preview:** `resources/views/admin/dev/components/chart-bar.blade.php`

### API final consolidada

| Prop         | Tipo      | Default               |
| ------------ | --------- | --------------------- |
| `title`      | `string`  | —                     |
| `subtitle`   | `?string` | `null`                |
| `chartId`    | `?string` | auto                  |
| `height`     | `int`     | `320`                 |
| `series`     | `array`   | `[]`                  |
| `categories` | `array`   | `[]`                  |
| `colors`     | `array`   | `['--color-primary']` |
| `options`    | `array`   | `[]`                  |

### Observações de implementação

- o wrapper final compõe `x-admin.chart-card` e injeta um host `data-af-chart`
- a bridge `resources/js/admin/charts.js` mapeia `type: 'bar'` para ApexCharts horizontal
- o preview cobre ranking simples e variação com ação no header
