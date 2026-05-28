<x-admin.layout title="Dashboard">
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card label="Registros" value="0" icon="tabler--database" color="primary" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card label="Usuários ativos" value="0" icon="tabler--users" color="success" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card label="Tarefas pendentes" value="0" icon="tabler--clock" color="warning" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card label="Alertas" value="0" icon="tabler--alert-triangle" color="danger" />
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <x-admin.chart-card title="Atividade mensal" subtitle="Últimos 12 meses">
                <x-admin.chart-line title="Atividade" :series="[]" :categories="[]" />
            </x-admin.chart-card>
        </div>
        <div class="col-lg-4">
            <livewire:admin.exemplo-counter />
        </div>
    </div>
</x-admin.layout>
