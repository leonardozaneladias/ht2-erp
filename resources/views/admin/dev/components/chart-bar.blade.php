@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-bar',
    'description' => 'Wrapper ApexCharts horizontal para comparação entre contratos, cursos e outras categorias ranqueadas.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.chart-bar
            title="Top contratos por adesões"
            subtitle="Comparativo horizontal com cor única"
            :categories="['UNESP 2026', 'USP 2026', 'UNICAMP 2026', 'FMU 2025', 'FMU 2026']"
            :series="[['name' => 'Adesões', 'data' => [120, 85, 60, 40, 30]]]"
        >
            <p>Ideal para rankings curtos em dashboard e relatórios rápidos.</p>
        </x-admin.chart-bar>

        <x-admin.chart-bar
            title="Cursos com maior renovação"
            subtitle="Exemplo com ação no header"
            :categories="['Direito', 'Medicina', 'ADM', 'Engenharia', 'Odonto']"
            :series="[['name' => 'Renovações', 'data' => [74, 66, 58, 49, 41]]]"
            :colors="['--color-info']"
        >
            <x-slot:headerActions>
                <x-shared.button variant="default" appearance="outline" size="sm" icon="tabler--filter">
                    Filtrar
                </x-shared.button>
            </x-slot:headerActions>

            <p>Bar continua focado em categorias comparáveis, não em eixo temporal.</p>
        </x-admin.chart-bar>
    </div>
@endsection
