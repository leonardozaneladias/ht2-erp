# Dashboard Analytics (Referência)

**Categoria:** Dashboard (referência visual, não componente)
**Origem Inspinia:** `resources/views/dashboard/analytics.blade.php`
**Plugins JS:** ApexCharts

---

## Descrição

**Não é um componente** — é um showcase do Inspinia que serve como **referência visual e estrutural** para construir o Dashboard 14.2 do Portal ArtFinal. A view real do ArtFinal é `resources/views/admin/dashboard/index.blade.php`.

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

## Tradução para o Dashboard 14.2

Conforme PRD §14.2, a estrutura do Dashboard ArtFinal é:

1. **KPIs (grid 4):** Contratos Ativos, Formandos Aderidos, Receita a Receber, Inadimplência
2. **Gráficos (grid 2):** Adesões por mês (column), Receita x Inadimplência (line dual)
3. **Meta de Formandos por Contrato:** tabela resumida com progress bar
4. **Últimas Adesões:** tabela 10 registros
5. **Parcelas Vencendo nos próximos 7 dias:** tabela 10 registros
6. **Alertas do Sistema:** alerts de diferentes severidades

---

## View proposta (ArtFinal)

```blade
{{-- resources/views/admin/dashboard/index.blade.php --}}
<x-admin.layout title="Dashboard" subtitle="Visão Gerencial">
    {{-- 1. KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.kpi-card
            label="Contratos Ativos"
            :value="$kpis->contratosAtivos"
            icon="tabler--file-text"
            color="primary"
            :href="route('admin.contratos.index')"
        />
        <x-admin.kpi-card
            label="Formandos Aderidos"
            :value="$kpis->formandosAderidos"
            icon="tabler--users"
            color="success"
            :href="route('admin.formandos.index')"
        />
        <x-admin.kpi-card
            label="Receita a Receber"
            :value="MoneyHelper::format($kpis->receitaPendenteCentavos)"
            icon="tabler--cash"
            color="warning"
        />
        <x-admin.kpi-card
            label="Inadimplência"
            :value="number_format($kpis->inadimplenciaPct, 1, ',', '.').'%'"
            icon="tabler--alert-triangle"
            color="danger"
        />
    </div>

    {{-- 2. Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-admin.chart-card title="Adesões por Mês (12 meses)" chart-id="adesoes-mes">
            <livewire:admin.dashboard.grafico-adesoes-mensais chart-id="adesoes-mes" />
        </x-admin.chart-card>

        <x-admin.chart-card title="Receita x Inadimplência" chart-id="rec-inad">
            <livewire:admin.dashboard.grafico-receita-inadimplencia chart-id="rec-inad" />
        </x-admin.chart-card>
    </div>

    {{-- 3. Meta de Formandos por Contrato --}}
    <x-shared.card title="Meta de Formandos por Contrato" class="mb-6">
        <livewire:admin.dashboard.tabela-meta-contratos />
    </x-shared.card>

    {{-- 4 + 5. Últimas Adesões + Parcelas Vencendo --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-shared.card title="Últimas Adesões">
            <livewire:admin.dashboard.tabela-ultimas-adesoes />
        </x-shared.card>

        <x-shared.card title="Parcelas Vencendo (7 dias)">
            <livewire:admin.dashboard.tabela-parcelas-vencendo />
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

## Mapeamento no PRD

**Tela 14.2 — Dashboard Administrativo** (entrada principal após login).

---

## Classificação

| Critério         | Valor                      |
| ---------------- | -------------------------- |
| **Vai usar**     | 🟢 Sim (referência visual) |
| **Prioridade**   | P3 (Sprint 16)             |
| **Complexidade** | Média (composição)         |
| **Status**       | 🔴 Não iniciado            |

---

## Notas de Adaptação

1. **Não é componente** — é uma view do admin composta pelos demais componentes
2. **Livewire full-page** recomendado para o dashboard — cada bloco é Livewire independente para permitir refresh isolado
3. **Cache de KPIs** — valores agregados devem ser cached (Redis) por 1-5min, invalidados em eventos (adesão criada, pagamento baixado)
4. **Layout responsive:** grid 1 col mobile, 2 tablets, 4 KPIs desktop
5. **Alertas dinâmicos** — computed via queries que verificam conditions do PRD (contratos sem programação, programações vencendo, parcelas vencidas)
