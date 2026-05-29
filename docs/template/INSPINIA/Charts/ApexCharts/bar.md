# ApexCharts — Bar

**Categoria:** Chart
**Origem Inspinia:** `resources/views/charts/apex/bar.blade.php`
**Plugins JS:** ApexCharts 5.3.5
**Uso típico:** Dashboard — "Registros por Mês"

---

## Descrição

Gráfico de barras horizontais (ou verticais — ver `column.md`). Bar tradicionalmente é horizontal em ApexCharts; **bar vertical = column**. Usaremos **bar** para comparação entre categorias (ex: categoria x quantidade de pedidos) e **column** para séries temporais (registros por mês).

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
            name: 'Pedidos',
            data: [120, 85, 60, 40, 30],
        },
    ],
    xaxis: {
        categories: ['Eletrônicos', 'Vestuário', 'Alimentos', 'Livros', 'Outros'],
    },
    colors: ['#5B73E8'],
    dataLabels: { enabled: true, offsetX: 20 },
};

new ApexCharts(document.querySelector('#chart-bar'), options).render();
```

---

## Exemplo de Uso

### Dashboard — Top categorias por pedidos

```blade
<x-admin.chart-card title="Top Categorias por Pedidos" chart-id="top-categorias" :height="320">
    <livewire:admin.dashboard.grafico-top-categorias chart-id="top-categorias" />
</x-admin.chart-card>
```

```php
// app/Livewire/Admin/Dashboard/GraficoTopCategorias.php
class GraficoTopCategorias extends Component
{
    public string $chartId;

    public function render()
    {
        $categorias = Categoria::withCount('pedidos')
            ->orderByDesc('pedidos_count')
            ->limit(10)
            ->get();

        $this->dispatch('chart-update',
            chartId: $this->chartId,
            type: 'bar',
            data: [
                'series' => [['name' => 'Pedidos', 'data' => $categorias->pluck('pedidos_count')->toArray()]],
                'categories' => $categorias->map(fn($c) => $c->nome)->toArray(),
            ]
        );

        return view('livewire.admin.dashboard.grafico-top-categorias');
    }
}
```

---

## Casos de uso típicos

| Contexto    | Uso                                                            |
| ----------- | ------------------------------------------------------------- |
| Dashboard   | "Registros por Mês" — **na verdade é column (ver column.md)** |
| Relatórios  | Top categorias por pedidos (opcional)                         |

---

## Classificação

| Critério         | Valor                       |
| ---------------- | --------------------------- |
| **Vai usar**     | 🟢 Sim                      |
| **Complexidade** | Trivial (config ApexCharts) |
| **Status**       | 🟢 Concluído                |

---

## Notas de Adaptação

1. **Bar vs Column:** bar é horizontal (`horizontal: true`), column é vertical. O Inspinia separa em `bar.blade.php` e `column.blade.php` mas o tipo JS é o mesmo (`type: 'bar'` + `plotOptions.bar.horizontal`)
2. **Data labels** dentro das barras ou ao lado — configurar em `dataLabels`
3. **Cor única** via `colors: ['#5B73E8']`
4. **Grouped/Stacked** (14 variantes do Inspinia) — fora do escopo por enquanto

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

- o wrapper final compõe `x-admin.chart-card` e injeta um host `data-chart`
- a bridge `resources/js/admin/charts.js` mapeia `type: 'bar'` para ApexCharts horizontal
- o preview cobre ranking simples e variação com ação no header
