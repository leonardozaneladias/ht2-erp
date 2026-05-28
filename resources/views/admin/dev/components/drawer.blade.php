@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.drawer',
    'description' => 'Painel lateral admin-only para filtros avançados, formulários curtos e previews sem sair da listagem.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Drawers de exemplo" subtitle="Abrir para validar posição, largura e footer fixo">
            <div class="flex flex-wrap gap-3">
                <x-shared.button variant="primary" icon="tabler--plus" data-hs-overlay="#drawer-cadastro">
                    Novo índice
                </x-shared.button>

                <x-shared.button
                    variant="default"
                    appearance="outline"
                    icon="tabler--filter"
                    data-hs-overlay="#drawer-filtros"
                >
                    Filtros avançados
                </x-shared.button>

                <x-shared.button
                    variant="info"
                    appearance="ghost"
                    icon="tabler--layout-sidebar-left-collapse"
                    data-hs-overlay="#drawer-bottom"
                >
                    Drawer inferior
                </x-shared.button>
            </div>
        </x-shared.card>

        <x-admin.drawer id="drawer-cadastro" title="Novo índice de reajuste" size="lg">
            <div class="space-y-4">
                <div>
                    <label class="text-body-color mb-2 block text-sm font-medium" for="indice-nome">Nome</label>
                    <input
                        id="indice-nome"
                        type="text"
                        class="border-default-300 bg-card w-full rounded border px-3 py-2 text-sm"
                        placeholder="Ex.: IPCA Acumulado"
                    />
                </div>

                <div>
                    <label class="text-body-color mb-2 block text-sm font-medium" for="indice-referencia"
                        >Referência</label
                    >
                    <input
                        id="indice-referencia"
                        type="text"
                        class="border-default-300 bg-card w-full rounded border px-3 py-2 text-sm"
                        placeholder="04/2026"
                    />
                </div>

                <div>
                    <label class="text-body-color mb-2 block text-sm font-medium" for="indice-percentual"
                        >Percentual</label
                    >
                    <input
                        id="indice-percentual"
                        type="text"
                        class="border-default-300 bg-card w-full rounded border px-3 py-2 text-sm"
                        placeholder="3,25%"
                    />
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-shared.button variant="default" appearance="outline" data-hs-overlay="#drawer-cadastro">
                        Cancelar
                    </x-shared.button>
                    <x-shared.button variant="primary"> Salvar índice </x-shared.button>
                </div>
            </x-slot:footer>
        </x-admin.drawer>

        <x-admin.drawer id="drawer-filtros" title="Filtros avançados" size="xl">
            <div class="space-y-4">
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-body-color font-medium">Contrato</p>
                    <p class="text-default-400 mt-1 text-sm">Filtro mockado para preview visual do espaço interno.</p>
                </div>

                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-body-color font-medium">Curso</p>
                    <p class="text-default-400 mt-1 text-sm">Outro bloco de filtro que normalmente seria um `select-search`.</p>
                </div>

                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-body-color font-medium">Período</p>
                    <p class="text-default-400 mt-1 text-sm">Espaço reservado para date range e filtros de status.</p>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-between gap-2">
                    <x-shared.button variant="default" appearance="ghost">Limpar</x-shared.button>
                    <x-shared.button variant="primary" data-hs-overlay="#drawer-filtros">Aplicar</x-shared.button>
                </div>
            </x-slot:footer>
        </x-admin.drawer>

        <x-admin.drawer
            id="drawer-bottom"
            title="Resumo rápido"
            position="bottom"
            size="md"
            body-scroll
            :backdrop="false"
        >
            <div class="grid gap-4 md:grid-cols-3">
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-default-400 text-sm">Contratos ativos</p>
                    <p class="text-body-color mt-2 text-xl font-semibold">42</p>
                </div>
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-default-400 text-sm">Parcelas vencidas</p>
                    <p class="text-body-color mt-2 text-xl font-semibold">18</p>
                </div>
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-default-400 text-sm">Receita prevista</p>
                    <p class="text-body-color mt-2 text-xl font-semibold">R$ 92,4 mil</p>
                </div>
            </div>
        </x-admin.drawer>
    </div>
@endsection
