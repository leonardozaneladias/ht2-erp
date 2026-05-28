@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-card',
    'description' => 'Wrapper visual para gráficos do dashboard e relatórios, padronizando header, filtros e área reservada do chart.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.chart-card title="Adesões por mês" subtitle="Wrapper com filtros no header" :height="320">
            <x-slot:headerActions>
                <x-shared.select
                    name="chart_period"
                    :options="[
                        '30d' => 'Últimos 30 dias',
                        '90d' => 'Últimos 90 dias',
                        '12m' => 'Últimos 12 meses',
                    ]"
                    selected="12m"
                    class="min-w-44"
                />
            </x-slot:headerActions>

            <x-slot:chart>
                <div
                    class="from-primary/5 flex h-80 items-end gap-3 rounded-lg bg-gradient-to-b to-transparent px-4 py-5"
                >
                    @foreach ([36, 52, 41, 64, 58, 73, 61, 79, 66, 88, 74, 92] as $index => $height)
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="bg-primary/80 w-full rounded-t-lg" style="height: {{ $height * 2 }}px"></div>
                            <span
                                class="text-2xs text-default-400 font-medium"
                                >{{ now()->startOfYear()->addMonths($index)->translatedFormat('M') }}</span
                            >
                        </div>
                    @endforeach
                </div>
            </x-slot:chart>

            <p>Usar este wrapper com os Livewire charts reais do dashboard, mantendo o mesmo header e a mesma área reservada para o ApexCharts.</p>
        </x-admin.chart-card>

        <x-admin.chart-card
            title="Receita x inadimplência"
            subtitle="Comparativo mensal em layout mais analítico"
            :height="320"
        >
            <x-slot:headerActions>
                <x-shared.button variant="default" appearance="outline" size="sm" icon="tabler--download">
                    Exportar imagem
                </x-shared.button>
            </x-slot:headerActions>

            <x-slot:chart>
                <div class="border-default-200/70 bg-card relative h-80 overflow-hidden rounded-lg border px-4 py-5">
                    <div class="border-default-300 absolute inset-x-4 top-1/2 border-t border-dashed"></div>
                    <svg viewBox="0 0 420 220" class="text-default-400 h-full w-full">
                        <polyline
                            fill="none"
                            stroke="rgba(91, 115, 232, 0.9)"
                            stroke-width="4"
                            points="0,170 52,158 104,146 156,134 208,110 260,118 312,90 364,76 420,64"
                        />
                        <polyline
                            fill="none"
                            stroke="rgba(239, 68, 68, 0.75)"
                            stroke-width="4"
                            points="0,120 52,116 104,112 156,122 208,130 260,126 312,118 364,124 420,112"
                        />
                    </svg>
                    <div class="text-2xs text-default-400 absolute inset-x-6 bottom-4 flex justify-between font-medium">
                        @foreach (['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set'] as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </x-slot:chart>

            <div class="flex flex-wrap items-center gap-4">
                <span class="text-default-400 inline-flex items-center gap-2 text-xs"
                    ><span class="bg-primary size-2 rounded-full"></span> Receita</span
                >
                <span class="text-default-400 inline-flex items-center gap-2 text-xs"
                    ><span class="bg-danger size-2 rounded-full"></span> Inadimplência</span
                >
            </div>
        </x-admin.chart-card>
    </div>
@endsection
