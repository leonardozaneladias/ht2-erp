# Dashboard Analytics (Referência)

**Categoria:** Dashboard (referência visual, não componente)
**Origem Inspinia:** `resources/views/dashboard/analytics.blade.php`
**Plugins JS:** ApexCharts

---

## Descrição

**Não é um componente** — é um showcase do Inspinia que serve como **referência visual e estrutural** para construir o dashboard administrativo da aplicação. A view real fica em `resources/views/admin/dashboard/index.blade.php`.

---

## Estrutura visual de referência (Inspinia Analytics)

```
┌──────────────────────────────────────────────────────────────┐
│ KPI KPI KPI KPI     (grid 4 colunas)                         │
├──────────────────────────────────────────────────────────────┤
│ GRÁFICO 1      │ GRÁFICO 2     (grid 2 colunas)              │
│                │                                             │
├────────────────┴────────────────────────────────────────────┤
│ TABELA RESUMIDA                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## Tradução para o dashboard administrativo

Estrutura típica de um dashboard gerencial:

1. **KPIs (grid 4):** Registros Ativos, Clientes, Receita a Receber, Pendências
2. **Gráficos (grid 2):** Pedidos por mês (column), Receita x Despesa (line dual)
3. **Meta por Categoria:** tabela resumida com progress bar
4. **Últimos Registros:** tabela 10 registros
5. **Itens a Vencer nos próximos 7 dias:** tabela 10 registros
6. **Alertas do Sistema:** alerts de diferentes severidades

---

## View proposta

```blade
{{-- resources/views/admin/dashboard/index.blade.php --}}
<x-admin.layout title="Dashboard" subtitle="Visão Gerencial">
    {{-- 1. KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.kpi-card
            label="Pedidos Ativos"
            :value="$kpis->pedidosAtivos"
            icon="tabler--file-text"
            color="primary"
            :href="route('admin.pedidos.index')"
        />
        <x-admin.kpi-card
            label="Clientes"
            :value="$kpis->clientes"
            icon="tabler--users"
            color="success"
            :href="route('admin.clientes.index')"
        />
        <x-admin.kpi-card
            label="Receita a Receber"
            :value="MoneyHelper::format($kpis->receitaPendenteCentavos)"
            icon="tabler--cash"
            color="warning"
        />
        <x-admin.kpi-card
            label="Pendências"
            :value="number_format($kpis->pendenciasPct, 1, ',', '.').'%'"
            icon="tabler--alert-triangle"
            color="danger"
        />
    </div>

    {{-- 2. Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-admin.chart-card title="Pedidos por Mês (12 meses)" chart-id="pedidos-mes">
            <livewire:admin.dashboard.grafico-pedidos-mensais chart-id="pedidos-mes" />
        </x-admin.chart-card>

        <x-admin.chart-card title="Receita x Despesa" chart-id="rec-desp">
            <livewire:admin.dashboard.grafico-receita-despesa chart-id="rec-desp" />
        </x-admin.chart-card>
    </div>

    {{-- 3. Meta por Categoria --}}
    <x-shared.card title="Meta por Categoria" class="mb-6">
        <livewire:admin.dashboard.tabela-meta-categorias />
    </x-shared.card>

    {{-- 4 + 5. Últimos Registros + Itens a Vencer --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-shared.card title="Últimos Registros">
            <livewire:admin.dashboard.tabela-ultimos-registros />
        </x-shared.card>

        <x-shared.card title="Itens a Vencer (7 dias)">
            <livewire:admin.dashboard.tabela-itens-a-vencer />
        </x-shared.card>
    </div>

    {{-- 6. Alertas do Sistema --}}
    <livewire:admin.dashboard.alertas-sistema />
</x-admin.layout>
```

---

## Componentes dependentes (todos já documentados)

- `<x-admin.kpi-card>` — `Data/kpi-card.md`
- `<x-admin.chart-card>` + `column.md` + `line.md` — Charts/ApexCharts/
- `<x-shared.card>` — `UI/card.md`
- `<x-shared.progress-bar>` — `UI/progress.md`
- `<x-shared.status-badge>` — `Data/status-badge.md`
- `<x-shared.alert>` — `UI/alert.md`

---

## Onde se aplica

Dashboard administrativo (entrada principal após login).

---

## Classificação

| Critério         | Valor                      |
| ---------------- | -------------------------- |
| **Vai usar**     | 🟢 Sim (referência visual) |
| **Complexidade** | Média (composição)         |
| **Status**       | 🔴 Não iniciado            |

---

## Notas de Adaptação

1. **Não é componente** — é uma view do admin composta pelos demais componentes
2. **Livewire full-page** recomendado para o dashboard — cada bloco é Livewire independente para permitir refresh isolado
3. **Cache de KPIs** — valores agregados devem ser cached (Redis) por 1-5min, invalidados em eventos (registro criado, pagamento baixado)
4. **Layout responsive:** grid 1 col mobile, 2 tablets, 4 KPIs desktop
5. **Alertas dinâmicos** — computed via queries que verificam condições do domínio (registros pendentes, itens vencendo, prazos vencidos)
