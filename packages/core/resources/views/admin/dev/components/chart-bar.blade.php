@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-bar',
    'description' => 'Wrapper ApexCharts horizontal para comparação entre registros, categorias e outras dimensões ranqueadas.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.chart-bar
            title="Top equipes por adesões"
            subtitle="Comparativo horizontal com cor única"
            :categories="['Equipe Comercial', 'Equipe de Suporte', 'Equipe de Operações', 'Equipe Financeira', 'Equipe Diretoria']"
            :series="[['name' => 'Adesões', 'data' => [120, 85, 60, 40, 30]]]"
        >
            <p>Ideal para rankings curtos em dashboard e relatórios rápidos.</p>
        </x-admin.chart-bar>

        <x-admin.chart-bar
            title="Categorias com maior renovação"
            subtitle="Exemplo com ação no header"
            :categories="['Comercial', 'Suporte', 'Operações', 'Financeiro', 'Diretoria']"
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
