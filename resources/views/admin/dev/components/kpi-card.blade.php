@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.kpi-card',
    'description' => 'Card compacto de métrica para dashboard, topo de relatórios e totais rápidos do backoffice.',
])

@section ('preview')
    <div class="grid gap-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.kpi-card
                label="Contratos Ativos"
                value="28"
                icon="tabler--file-text"
                color="primary"
                :trend="12.5"
                href="#!"
            />

            <x-admin.kpi-card
                label="Formandos Aderidos"
                value="1.284"
                icon="tabler--users"
                color="success"
                :trend="8.2"
                href="#!"
            >
                Meta mensal já em 72% do planejado.
            </x-admin.kpi-card>

            <x-admin.kpi-card
                label="Receita a Receber"
                value="R$ 182.450,00"
                icon="tabler--cash"
                color="warning"
                :trend="-3.1"
                trend-label="vs. última semana"
            />

            <x-admin.kpi-card
                label="Inadimplência"
                value="6,8%"
                icon="tabler--alert-triangle"
                color="danger"
                :trend="-1.4"
                trend-label="vs. último mês"
            />
        </div>

        <x-shared.card title="Uso financeiro" subtitle="Mesmo padrão aplicado aos totalizadores do módulo de parcelas">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-admin.kpi-card label="Total Geral" value="R$ 482.320,00" icon="tabler--receipt-2" color="primary" />
                <x-admin.kpi-card
                    label="Pago"
                    value="R$ 301.780,00"
                    icon="tabler--circle-check"
                    color="success"
                    :trend="4.9"
                />
                <x-admin.kpi-card label="Pendente" value="R$ 118.540,00" icon="tabler--clock-hour-4" color="warning" />
                <x-admin.kpi-card
                    label="Vencido"
                    value="R$ 62.000,00"
                    icon="tabler--alert-octagon"
                    color="danger"
                    :trend="-2.6"
                    trend-label="vs. último fechamento"
                />
            </div>
        </x-shared.card>
    </div>
@endsection
