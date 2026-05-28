@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-column',
    'description' => 'Wrapper ApexCharts vertical para adesões por mês, receita por contrato e séries temporais com eixo X categórico.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.chart-column
            title="Adesões por mês"
            subtitle="Últimos 12 meses"
            :categories="['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']"
            :series="[['name' => 'Adesões', 'data' => [45, 52, 68, 74, 80, 95, 110, 98, 105, 115, 130, 142]]]"
        />

        <x-admin.chart-column
            title="Receita por contrato"
            subtitle="Top 6 contratos do trimestre"
            :categories="['AF-001', 'AF-014', 'AF-025', 'AF-031', 'AF-040', 'AF-052']"
            :series="[['name' => 'Receita', 'data' => [42000, 38500, 36200, 31800, 27600, 24100]]]"
            :colors="['--color-warning']"
        >
            <p>Column é a escolha natural quando o eixo X representa tempo ou agrupamentos curtos ordenados.</p>
        </x-admin.chart-column>
    </div>
@endsection
