@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.breadcrumb',
    'description' => 'Breadcrumb standalone para contextos internos como drawers, cards, modais e páginas sem page-header completo.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Breadcrumb básico" subtitle="Com ícone no primeiro item">
            <x-shared.breadcrumb
                :items="[
                ['label' => 'Admin', 'url' => '#!', 'icon' => 'tabler--home'],
                ['label' => 'Contratos', 'url' => '#!'],
                ['label' => 'Editar contrato'],
            ]"
            />
        </x-shared.card>

        <x-shared.card title="Dentro de um card de detalhe" subtitle="Cenário próximo ao domínio">
            <x-shared.breadcrumb
                :items="[
                ['label' => 'Formandos', 'url' => '#!'],
                ['label' => 'ADM-2026-05', 'url' => '#!'],
                ['label' => 'Maria Eduarda Souza'],
            ]"
                divider="/"
            />

            <div class="border-default-300 bg-light/40 text-default-400 mt-4 rounded-lg border p-4 text-sm">
                Ficha resumida de um formando com breadcrumb complementar ao contexto da tela principal.
            </div>
        </x-shared.card>
    </div>
@endsection
