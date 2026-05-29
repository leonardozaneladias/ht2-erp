# Chart Card (Wrapper ApexCharts)

**Categoria:** Chart wrapper
**Origem Inspinia:** `resources/views/charts/apex/*.blade.php` (padrão comum)
**Plugins JS:** ApexCharts 5.3.5
**Plugins CSS:** ApexCharts CSS + `.card` do Inspinia

---

## Descrição

Wrapper Blade que envolve um gráfico ApexCharts em um `card` com título e slots para filtros. Padroniza a moldura visual dos 4 tipos de gráfico (bar, line, column, pie). O chart em si é inicializado via Livewire component filho que dispatcha configs para ApexCharts.

> **Padrão:** cada tipo de gráfico é um **Livewire component** que recebe dados do PHP, renderiza o `<div id="...">` e emite evento JS com a config — mantém os dados no servidor e o render no cliente.

---

## Código Original (Inspinia — essência)

```html
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Receita por Mês</h4>
    </div>
    <div class="card-body">
        <div dir="ltr">
            <div class="apex-charts" id="grafico-receita"></div>
        </div>
    </div>
</div>
```

```js
// resources/js/pages/chart-apex-bar.js
const options = {
    chart: { type: 'bar', height: 350 },
    series: [{ name: 'Receita', data: [12000, 15000, 18000, ...] }],
    xaxis: { categories: ['Jan', 'Fev', 'Mar', ...] },
}
new ApexCharts(document.querySelector('#grafico-receita'), options).render()
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.chart-card>`
**Arquivo:** `resources/views/components/admin/chart-card.blade.php`

### Props

| Prop      | Tipo     | Default | Descrição                      |
| --------- | -------- | ------- | ------------------------------ |
| `title`   | `string` | —       | Título do card                 |
| `chartId` | `string` | auto    | ID único do `<div>` do gráfico |
| `height`  | `int`    | `350`   | Altura em px                   |

### Slots

- `$headerActions` — filtros no header (ex: select de período)
- `$slot` — opcional — só necessário se quiser texto adicional. Normalmente o chart sozinho é suficiente

### Código

```blade
{{-- resources/views/components/admin/chart-card.blade.php --}}
@props ([
    'title',
    'chartId' => 'chart-' . \Illuminate\Support\Str::random(6),
    'height' => 350,
])

<div {{ $attributes->class(['card']) }}>
    <div class="card-header">
        <h4 class="card-title">{{ $title }}</h4>
        @isset ($headerActions)
            <div class="ms-auto flex gap-2">{{ $headerActions }}</div>
        @endisset
    </div>
    <div class="card-body">
        <div dir="ltr">
            <div class="apex-charts" id="{{ $chartId }}" style="min-height: {{ $height }}px" wire:ignore></div>
        </div>
        {{ $slot }}
    </div>
</div>
```

---

## Exemplo de Uso

```blade
<x-admin.chart-card title="Pedidos por Mês" chart-id="grafico-pedidos" :height="320">
    <x-slot:headerActions>
        <x-shared.select name="categoria_filter" :options="$categorias" wire:model.live="filtroCategoria" />
    </x-slot:headerActions>

    <livewire:admin.dashboard.grafico-pedidos chart-id="grafico-pedidos" :filtro="$filtroCategoria" />
</x-admin.chart-card>
```

---

## Padrão Livewire para gráficos

```php
// app/Livewire/Admin/Dashboard/GraficoPedidos.php
class GraficoPedidos extends Component
{
    public string $chartId;
    public ?int $filtro = null;

    #[Computed]
    public function dados(): array
    {
        $meses = collect(range(11, 0))->map(fn($i) => now()->subMonths($i));
        $series = $meses->map(fn($m) => Pedido::whereYear('created_at', $m->year)
            ->whereMonth('created_at', $m->month)
            ->when($this->filtro, fn($q) => $q->where('categoria_id', $this->filtro))
            ->count())
            ->toArray();

        return [
            'categories' => $meses->map->format('M/y')->toArray(),
            'series' => [['name' => 'Pedidos', 'data' => $series]],
        ];
    }

    public function render()
    {
        $this->dispatch('chart-update', chartId: $this->chartId, data: $this->dados);
        return view('livewire.admin.dashboard.grafico-pedidos');
    }
}
```

```blade
{{-- resources/views/livewire/admin/dashboard/grafico-pedidos.blade.php --}}
<div></div>
```

---

## Bridge JavaScript global

```js
// resources/js/admin/charts.js
import ApexCharts from 'apexcharts';

const charts = new Map();

function createOrUpdate(chartId, data, type = 'bar') {
    const el = document.getElementById(chartId);
    if (!el) return;

    const config = {
        chart: { type, height: el.offsetHeight || 320, toolbar: { show: false } },
        series: data.series,
        xaxis: { categories: data.categories },
        colors: ['#5B73E8'],
        dataLabels: { enabled: false },
    };

    if (charts.has(chartId)) {
        charts.get(chartId).updateOptions(config);
    } else {
        const chart = new ApexCharts(el, config);
        chart.render();
        charts.set(chartId, chart);
    }
}

document.addEventListener('livewire:init', () => {
    Livewire.on('chart-update', ({ chartId, data, type }) => {
        createOrUpdate(chartId, data, type);
    });
});
```

---

## Casos de uso típicos

| Contexto    | Gráfico                                     |  Tipo  |
| ----------- | ------------------------------------------- | :----: |
| Dashboard   | Registros por mês (12 meses)                |  bar   |
| Dashboard   | Receita x despesa (12 meses, linhas duplas) |  line  |
| Relatórios  | Receita por categoria                       | column |
| Relatórios  | Distribuição por tipo                       |  pie   |

---

## Classificação

| Critério         | Valor                                |
| ---------------- | ------------------------------------ |
| **Vai usar**     | 🟢 Sim                               |
| **Complexidade** | Média (bridge Livewire + ApexCharts) |
| **Status**       | 🟢 Concluído                         |

---

## Código Final Blade

**Arquivo:** `resources/views/components/admin/chart-card.blade.php`
**Preview:** `resources/views/admin/dev/components/chart-card.blade.php`

### API final consolidada

| Prop       | Tipo      | Default | Observação                               |
| ---------- | --------- | ------- | ---------------------------------------- |
| `title`    | `string`  | —       | Título do card                           |
| `subtitle` | `?string` | `null`  | Texto auxiliar opcional                  |
| `chartId`  | `?string` | auto    | ID reservado para a instância do gráfico |
| `height`   | `int`     | `350`   | Altura mínima da área reservada          |

### Slots finais

| Slot            | Uso                                                   |
| --------------- | ----------------------------------------------------- |
| `headerActions` | Filtros e ações do header                             |
| `chart`         | Área visual do gráfico real ou placeholder controlado |
| default         | Texto auxiliar abaixo da área do gráfico              |

### Código

```blade
<x-admin.chart-card title="Pedidos por mês" subtitle="Últimos 12 meses" chart-id="grafico-pedidos" :height="320">
    <x-slot:headerActions>
        <x-shared.select name="periodo" :options="['30d' => '30 dias', '12m' => '12 meses']" selected="12m" />
    </x-slot:headerActions>

    <x-slot:chart>
        <div id="grafico-pedidos" class="h-80" wire:ignore></div>
    </x-slot:chart>
</x-admin.chart-card>
```

---

## Notas de Adaptação

1. **O lote atual entrega o wrapper visual**, não a bridge completa de ApexCharts; os charts reais (`bar`, `line`, `column`, `pie`) continuam no ciclo seguinte
2. **`chartId`** já fica reservado no contrato Blade para os componentes/bridges futuros reaproveitarem sem quebra de API
3. **`chart` slot** permite previews e integrações progressivas sem obrigar a presença do JS do chart em toda tela
4. **`wire:ignore`** continua sendo o padrão recomendado para o host do gráfico real
5. **Header consistente** com `headerActions` reduz duplicação entre dashboard e relatórios
6. **Sem componente separado de widget/metric**: o wrapper oficial de charts é só `x-admin.chart-card`
