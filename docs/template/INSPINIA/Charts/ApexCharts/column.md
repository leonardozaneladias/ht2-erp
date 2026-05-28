# ApexCharts — Column

**Categoria:** Chart
**Origem Inspinia:** `resources/views/charts/apex/column.blade.php`
**Plugins JS:** ApexCharts 5.3.5
**Uso no ArtFinal:** Dashboard 14.2 — "Adesões por Mês"; Relatórios 14.17

---

## Descrição

Barras **verticais** (ApexCharts considera column = bar com `horizontal: false`). Usado para séries temporais onde o eixo X é tempo.

---

## Configuração mínima

```js
const options = {
    chart: {
        type: 'bar', // ApexCharts usa 'bar' com horizontal: false
        height: 320,
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '60%',
            borderRadius: 4,
            borderRadiusApplication: 'end',
        },
    },
    series: [{ name: 'Adesões', data: [45, 52, 68, 74, 80, 95, 110, 98, 105, 115, 130, 142] }],
    xaxis: {
        categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
    },
    colors: ['#5B73E8'],
    dataLabels: { enabled: false },
};

new ApexCharts(document.querySelector('#chart-column'), options).render();
```

---

## Exemplo de Uso (Dashboard 14.2 — Adesões por Mês)

```blade
<x-admin.chart-card title="Adesões por Mês (últimos 12 meses)" chart-id="adesoes-mes" :height="320">
    <x-slot:headerActions>
        <x-shared.select
            name="filtro_contrato"
            :options="['' => 'Todos os contratos'] + $contratos->pluck('codigo_turma', 'id')->toArray()"
            wire:model.live="filtroContrato"
        />
    </x-slot:headerActions>

    <livewire:admin.dashboard.grafico-adesoes-mensais chart-id="adesoes-mes" :filtro-contrato="$filtroContrato" />
</x-admin.chart-card>
```

```php
class GraficoAdesoesMensais extends Component
{
    public string $chartId;
    public ?int $filtroContrato = null;

    public function render()
    {
        $meses = collect(range(11, 0))->map(fn($i) => now()->subMonths($i));

        $adesoes = $meses->map(fn($m) => Adesao::ativas()
            ->whereYear('created_at', $m->year)
            ->whereMonth('created_at', $m->month)
            ->when($this->filtroContrato, fn($q) => $q->where('contrato_id', $this->filtroContrato))
            ->count()
        )->toArray();

        $this->dispatch('chart-update',
            chartId: $this->chartId,
            type: 'column',  // bridge JS mapeia para 'bar' horizontal: false
            data: [
                'series' => [['name' => 'Adesões', 'data' => $adesoes]],
                'categories' => $meses->map->format('M/y')->toArray(),
            ]
        );

        return view('livewire.admin.dashboard.grafico-adesoes-mensais');
    }
}
```

---

## Mapeamento no PRD

| Tela             | Uso                                                           |
| ---------------- | ------------------------------------------------------------- |
| 14.2 Dashboard   | **"Adesões por Mês" (últimos 12 meses, filtro por contrato)** |
| 14.17 Relatórios | Receita por contrato (bar agrupado)                           |

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

1. **Bridge JS mapeia `type: 'column'` → `type: 'bar' + horizontal: false`** — mantém semântica clara no backend
2. **`columnWidth: '60%'`** — barras não coladas
3. **`borderRadius: 4`** — cantos arredondados no topo
4. **Sem data labels** por padrão — tooltip revela o valor no hover
5. **Cor única** — chart simples, sem legenda

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/admin/chart-column.blade.php`
- `resources/js/admin/charts.js`
  **Preview:** `resources/views/admin/dev/components/chart-column.blade.php`

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

- o backend continua falando `column`, e a bridge JS traduz isso para ApexCharts `bar` com `horizontal: false`
- o preview cobre adesões por mês e receita por contrato
