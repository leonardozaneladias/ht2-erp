<x-admin.layout
    title="Preview • x-admin.footer"
    subtitle="Layout e navegação"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dev Components', 'url' => route('admin.dev.components.index')],
        ['label' => 'Footer', 'current' => true],
    ]"
>
    <x-slot:actions>
        <a class="btn bg-light text-dark hover:text-primary" href="{{ route('admin.dev.components.index') }}">
            <i class="iconify tabler--arrow-left text-base"></i>
            Voltar ao índice
        </a>
    </x-slot:actions>

    <div class="grid gap-6">
        <x-shared.card title="O que validar" subtitle="Rodapé do shell admin">
            <ul class="text-default-400 space-y-2 text-sm">
                <li>Texto institucional simples, sem “mega footer”.</li>
                <li>Versão da aplicação via `config('app.version')`.</li>
                <li>Badge de ambiente fora de produção para facilitar suporte.</li>
                <li>Render automático ao final da `content-page` dentro do layout.</li>
            </ul>
        </x-shared.card>

        <x-shared.card title="Contexto de uso" subtitle="Admin corporativo">
            <p class="text-default-400 text-sm">O footer permanece enxuto para não competir com a navegação lateral nem com os cabeçalhos das telas. Se no futuro surgir necessidade de links institucionais, isso vira variante própria e não expande este componente.</p>
        </x-shared.card>
    </div>
</x-admin.layout>
