@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-line',
    'description' => 'Wrapper ApexCharts de linhas múltiplas para receita, inadimplência e séries temporais comparativas.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-admin.chart-line
            title="Receita x inadimplência"
            subtitle="Últimos 12 meses"
            :categories="['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']"
            :series="[
                ['name' => 'Receita', 'data' => [12000, 14000, 13500, 15000, 16000, 15500, 17000, 18000, 17500, 19000, 20000, 21000]],
                ['name' => 'Inadimplência', 'data' => [2000, 2500, 2200, 3000, 2800, 3200, 3500, 3000, 3400, 3100, 2800, 2900]],
            ]"
        >
            <x-slot:headerActions>
                <x-shared.select
                    name="line_period"
                    :options="['6m' => '6 meses', '12m' => '12 meses']"
                    selected="12m"
                    class="min-w-40"
                />
            </x-slot:headerActions>

            <p>As duas séries seguem a paleta semântica verde x vermelho para leitura imediata.</p>
        </x-admin.chart-line>
    </div>
@endsection
