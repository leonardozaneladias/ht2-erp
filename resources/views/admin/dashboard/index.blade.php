@php
    /** @var \App\DTOs\Admin\DashboardMetricsDTO $metricas */
@endphp

<x-admin.layout title="Dashboard">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.kpi-card
            label="Usuários admin"
            :value="number_format($metricas->totalUsuarios, 0, ',', '.')"
            icon="tabler--users"
            color="primary"
            :href="route('admin.usuarios.index')"
        />
        <x-admin.kpi-card
            label="Usuários ativos"
            :value="number_format($metricas->usuariosAtivos, 0, ',', '.')"
            icon="tabler--user-check"
            color="success"
        />
        <x-admin.kpi-card
            label="Empresas"
            :value="number_format($metricas->totalEmpresas, 0, ',', '.')"
            icon="tabler--building"
            color="info"
            :href="route('admin.empresas.index')"
        />
        <x-admin.kpi-card
            label="Eventos hoje"
            :value="number_format($metricas->eventosHoje, 0, ',', '.')"
            icon="tabler--activity"
            color="warning"
            :href="route('admin.auditoria.index')"
        />
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-admin.chart-line
                title="Novos usuários"
                subtitle="Últimos 6 meses"
                :series="[['name' => 'Novos usuários', 'data' => $metricas->serie]]"
                :categories="$metricas->categorias"
                :colors="['--color-primary']"
            />
        </div>
        <div>
            <livewire:admin.exemplo-counter />
        </div>
    </div>
</x-admin.layout>
