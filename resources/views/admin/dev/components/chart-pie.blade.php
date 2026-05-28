@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.chart-pie',
    'description' => 'Wrapper ApexCharts pie/donut para distribuição proporcional por modalidade, status ou categoria.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.chart-pie
            title="Distribuição por modalidade"
            subtitle="Donut com total central"
            :labels="['Boleto', 'Cartão', 'PIX', 'Link de Pagamento']"
            :series="[44, 55, 13, 27]"
        />

        <x-admin.chart-pie
            title="Status das parcelas"
            subtitle="Versão pie tradicional"
            type="pie"
            :labels="['Pagas', 'Pendentes', 'Vencidas']"
            :series="[62, 24, 14]"
            :colors="['--color-success', '--color-warning', '--color-danger']"
        >
            <p>Donut é o padrão preferido, mas o wrapper aceita `type=&quot;pie&quot;` quando o recorte tradicional fizer mais sentido.</p>
        </x-admin.chart-pie>
    </div>
@endsection
