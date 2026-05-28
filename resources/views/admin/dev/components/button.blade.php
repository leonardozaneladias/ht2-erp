@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.button',
    'description' => 'Botão base do sistema para ações primárias, links estilizados, botões compactos e triggers de overlay.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Aparências e variantes" subtitle="API final com `appearance` e alias compatível `style`">
            <div class="space-y-5">
                <div class="flex flex-wrap gap-3">
                    <x-shared.button variant="primary">Salvar</x-shared.button>
                    <x-shared.button variant="secondary">Duplicar</x-shared.button>
                    <x-shared.button variant="success" icon="tabler--circle-check">Publicar</x-shared.button>
                    <x-shared.button variant="danger" icon="tabler--trash">Excluir</x-shared.button>
                    <x-shared.button variant="default" appearance="outline">Cancelar</x-shared.button>
                    <x-shared.button variant="info" appearance="ghost" icon="tabler--filter">Filtrar</x-shared.button>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-shared.button variant="primary" size="sm" pill>Pequeno</x-shared.button>
                    <x-shared.button variant="warning" size="lg" pill icon="tabler--sparkles">Destacar</x-shared.button>
                    <x-shared.button variant="default" :href="'#!'" icon="tabler--external-link">
                        Abrir detalhe</x-shared.button
                    >
                    <x-shared.button
                        variant="dark"
                        icon-only
                        icon="tabler--settings"
                        aria-label="Abrir configurações"
                    />
                    <x-shared.button variant="primary" block icon="tabler--download">
                        Exportar relatório completo</x-shared.button
                    >
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Exemplo de toolbar" subtitle="Composição comum em cabeçalhos e tabelas">
            <div
                class="border-default-300 bg-light/30 flex flex-col gap-4 rounded-lg border p-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <p class="text-body-color font-semibold">Contratos</p>
                    <p class="text-default-400 text-sm">Listagem consolidada das turmas ativas e em negociação.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-shared.button variant="default" appearance="outline" icon="tabler--download">
                        CSV
                    </x-shared.button>
                    <x-shared.button variant="primary" icon="tabler--plus"> Novo contrato </x-shared.button>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
