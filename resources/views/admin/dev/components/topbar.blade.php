<x-admin.layout
    title="Preview • x-admin.topbar"
    subtitle="Layout e navegação"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dev Components', 'url' => route('admin.dev.components.index')],
        ['label' => 'Topbar', 'current' => true],
    ]"
>
    <x-slot:actions>
        <a class="btn bg-light text-dark hover:text-primary" href="{{ route('admin.dev.components.index') }}">
            <i class="iconify tabler--arrow-left text-base"></i>
            Voltar ao índice
        </a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="O que validar" subtitle="Header fixo do admin">
            <ul class="text-default-400 space-y-2 text-sm">
                <li>Busca global com fallback simples por rota nomeada.</li>
                <li>Toggle de tema ligado ao bootstrap de `window.config`/`sessionStorage`.</li>
                <li>Sino de notificações como composição interna do topbar, sem virar componente autônomo.</li>
                <li>Menu de usuário reaproveitando dropdowns já consolidados no Batch 1.</li>
            </ul>
        </x-shared.card>

        <x-shared.card title="Escopo fechado" subtitle="O que saiu do template">
            <div class="text-default-400 space-y-3 text-sm">
                <p>O mega menu foi removido oficialmente do escopo do sistema e não reaparece neste batch.</p>
                <p>Apps grid, messages dropdown, language selector, monochrome mode e customizer continuam fora da versão final.</p>
            </div>
        </x-shared.card>
    </div>
</x-admin.layout>
