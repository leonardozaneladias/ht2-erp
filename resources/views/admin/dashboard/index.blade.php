<x-admin.layout title="Dashboard">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.kpi-card label="Registros" value="0" icon="tabler--database" color="primary" />
        <x-admin.kpi-card label="Usuários ativos" value="0" icon="tabler--users" color="success" />
        <x-admin.kpi-card label="Tarefas pendentes" value="0" icon="tabler--clock" color="warning" />
        <x-admin.kpi-card label="Alertas" value="0" icon="tabler--alert-triangle" color="danger" />
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-admin.chart-card title="Atividade mensal" subtitle="Últimos 12 meses">
                <x-admin.chart-line title="Atividade" :series="[]" :categories="[]" />
            </x-admin.chart-card>
        </div>
        <div>
            <livewire:admin.exemplo-counter />
        </div>
    </div>
</x-admin.layout>
