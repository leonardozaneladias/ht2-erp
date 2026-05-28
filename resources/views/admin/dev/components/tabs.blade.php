@extends ('admin.dev.components.shell', [
    'title' => 'Preview • família x-shared.tabs',
    'description' => 'Composição oficial com tab-nav, tab-trigger e tab-panel sobre Preline.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Abas padrão" subtitle="Navegação horizontal com ícones">
            <x-shared.tab-nav>
                <x-shared.tab-trigger id="visao-geral" active icon="tabler--layout-dashboard">
                    Visão geral</x-shared.tab-trigger
                >
                <x-shared.tab-trigger id="pagamentos" icon="tabler--cash">Pagamentos</x-shared.tab-trigger>
                <x-shared.tab-trigger id="documentos" icon="tabler--files">Documentos</x-shared.tab-trigger>
                <x-shared.tab-trigger id="historico" icon="tabler--history">Histórico</x-shared.tab-trigger>
            </x-shared.tab-nav>

            <div class="mt-5 grid gap-4">
                <x-shared.tab-panel id="visao-geral" active>
                    <x-shared.card title="Resumo do contrato" subtitle="Painel ativo por padrão">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                                <p class="text-default-400 text-sm">Turma</p>
                                <p class="text-body-color mt-2 text-lg font-semibold">ADM-2026-01</p>
                            </div>
                            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                                <p class="text-default-400 text-sm">Formandos</p>
                                <p class="text-body-color mt-2 text-lg font-semibold">183 confirmados</p>
                            </div>
                            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                                <p class="text-default-400 text-sm">Receita prevista</p>
                                <p class="text-body-color mt-2 text-lg font-semibold">R$ 246.900,00</p>
                            </div>
                        </div>
                    </x-shared.card>
                </x-shared.tab-panel>

                <x-shared.tab-panel id="pagamentos">
                    <x-shared.empty-state
                        size="sm"
                        icon="tabler--receipt-off"
                        title="Sem pagamentos conciliados"
                        description="As conciliações deste contrato aparecerão aqui assim que o financeiro começar a processar as parcelas."
                    />
                </x-shared.tab-panel>

                <x-shared.tab-panel id="documentos">
                    <x-shared.alert variant="info" title="Snapshot documental">
                        O termo atual, comprovantes e contratos versionados ficam centralizados nesta aba.
                    </x-shared.alert>
                </x-shared.tab-panel>

                <x-shared.tab-panel id="historico">
                    <div class="border-default-300 bg-light/40 text-default-400 rounded-2xl border p-4 text-sm">
                        A timeline detalhada continua sendo uma view inspirada na página de timeline do Inspinia, e não
                        um componente genérico separado.
                    </div>
                </x-shared.tab-panel>
            </div>
        </x-shared.card>

        <x-shared.card title="Abas justified" subtitle="Úteis para formulários administrativos densos">
            <x-shared.tab-nav justified>
                <x-shared.tab-trigger id="dados-form" active icon="tabler--user" justified>Dados</x-shared.tab-trigger>
                <x-shared.tab-trigger id="regras-form" icon="tabler--adjustments" justified>
                    Regras</x-shared.tab-trigger
                >
                <x-shared.tab-trigger id="integracoes-form" icon="tabler--plug" justified>
                    Integrações</x-shared.tab-trigger
                >
            </x-shared.tab-nav>

            <div class="mt-5">
                <x-shared.tab-panel id="dados-form" active>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                            <p class="text-default-400 text-sm">Configuração</p>
                            <p class="text-body-color mt-2 font-semibold">Empresa padrão</p>
                        </div>
                        <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                            <p class="text-default-400 text-sm">Notificações</p>
                            <p class="text-body-color mt-2 font-semibold">Ativas</p>
                        </div>
                    </div>
                </x-shared.tab-panel>

                <x-shared.tab-panel id="regras-form">
                    <div class="border-default-300 bg-light/40 text-default-400 rounded-2xl border p-4 text-sm">
                        Regras de vencimento, índices e tolerâncias seriam editadas aqui.
                    </div>
                </x-shared.tab-panel>

                <x-shared.tab-panel id="integracoes-form">
                    <div class="border-default-300 bg-light/40 text-default-400 rounded-2xl border p-4 text-sm">
                        Gateways, webhooks e chaves externas podem ocupar esta aba em telas reais.
                    </div>
                </x-shared.tab-panel>
            </div>
        </x-shared.card>
    </div>
@endsection
