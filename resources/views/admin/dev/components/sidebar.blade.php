<x-admin.layout
    title="Preview • x-admin.sidebar"
    subtitle="Layout e navegação"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dev Components', 'url' => route('admin.dev.components.index')],
        ['label' => 'Sidebar', 'current' => true],
    ]"
>
    <x-slot:actions>
        <a class="btn bg-light text-dark hover:text-primary" href="{{ route('admin.dev.components.index') }}">
            <i class="iconify tabler--arrow-left text-base"></i>
            Voltar ao índice
        </a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="O que validar" subtitle="Navegação lateral fixa">
            <ul class="text-default-400 space-y-2 text-sm">
                <li>Seções em PT-BR alinhadas ao PRD e ao mapa de telas.</li>
                <li>Accordion em 2 níveis para Registros, Produtos, Usuários, Financeiro e Configurações.</li>
                <li>Card do usuário com menu rápido e fallback visual para ambiente de preview.</li>
                <li>Mesmo sem admin autenticado, o preview mostra a árvore completa para inspeção local.</li>
            </ul>
        </x-shared.card>

        <x-shared.card title="Decisões aplicadas" subtitle="Consolidação do batch">
            <div class="text-default-400 space-y-3 text-sm">
                <p>O menu segue hardcoded por enquanto, como previsto na doc, e continua preparado para gates de permissão sem exigir class-based component.</p>
                <p>O mega menu do template não entra aqui: toda a navegação principal do sistema permanece concentrada no sidebar.</p>
            </div>
        </x-shared.card>
    </div>
</x-admin.layout>
