<x-admin.layout
    title="Preview • x-admin.page-header"
    subtitle="Layout e navegação"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dev Components', 'url' => route('admin.dev.components.index')],
        ['label' => 'Page Header', 'current' => true],
    ]"
>
    <x-slot:actions>
        <x-shared.button variant="default" appearance="outline" icon="tabler--download"> CSV </x-shared.button>
        <x-shared.button variant="primary" icon="tabler--plus"> Novo contrato </x-shared.button>
    </x-slot:actions>

    <div class="grid gap-6">
        <x-shared.card title="Breadcrumbs customizados" subtitle="Uso direto do componente">
            <x-admin.page-header
                title="Editar contrato"
                :breadcrumbs="[
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Cadastros'],
                    ['label' => 'Contratos', 'url' => route('admin.contratos.index')],
                    ['label' => 'ADM-2026-01'],
                    ['label' => 'Editar', 'current' => true],
                ]"
            >
                <x-slot:actions>
                    <x-shared.badge variant="warning" pill>Em revisão</x-shared.badge>
                    <x-shared.button variant="primary" icon="tabler--device-floppy">Salvar</x-shared.button>
                </x-slot:actions>
            </x-admin.page-header>
        </x-shared.card>

        <x-shared.card title="Forwarding pelo layout" subtitle="Header automático">
            <p class="text-default-400 text-sm">O cabeçalho do topo desta página foi renderizado automaticamente pelo <code>&lt;x-admin.layout&gt;</code>, incluindo forwarding de <code>actions</code> e breadcrumb explícito.</p>
        </x-shared.card>
    </div>
</x-admin.layout>
