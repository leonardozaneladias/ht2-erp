@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.card',
    'description' => 'Envelope base do Inspinia para dashboard, formulários, listas e blocos de contexto reutilizáveis.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Card simples" subtitle="Com body padrão">
            <p class="text-default-400 text-sm">Use este formato para agrupar conteúdo de dashboard, resumo de entidade e blocos de informação contextual.</p>
        </x-shared.card>

        <x-shared.card title="Header com ações" subtitle="Botões curtos à direita">
            <x-slot:headerActions>
                <x-shared.button variant="default" appearance="outline" size="sm">Exportar</x-shared.button>
                <x-shared.button variant="primary" size="sm" icon="tabler--plus">Novo</x-shared.button>
            </x-slot:headerActions>

            <div class="text-default-400 space-y-3 text-sm">
                <p>O slot `headerActions` mantém a composição limpa para listas e dashboards.</p>
                <p>O slot `header` continua disponível para headers mais complexos, como tabs ou títulos compostos.</p>
            </div>
        </x-shared.card>

        <x-shared.card
            title="Body sem padding"
            subtitle="Pronto para tabela ou conteúdo edge-to-edge"
            :body-padding="false"
        >
            <div class="divide-default-300 divide-y">
                @foreach (['Contrato', 'Instituição', 'Evento'] as $coluna)
                    <div class="grid grid-cols-3 gap-4 px-5 py-3 text-sm">
                        <span class="text-body-color font-medium">{{ $coluna }}</span>
                        <span class="text-default-400 col-span-2">Valor ilustrativo para preview</span>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Com footer" subtitle="Resumo final com timestamp">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-default-400">Recebido</p>
                    <p class="text-body-color mt-1 text-lg font-semibold">R$ 328.540,00</p>
                </div>
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <p class="text-default-400">Pendente</p>
                    <p class="text-body-color mt-1 text-lg font-semibold">R$ 42.180,00</p>
                </div>
            </div>

            <x-slot:footer>
                <span class="text-default-400 text-sm">Atualizado em 11/04/2026 às 14:35</span>
            </x-slot:footer>
        </x-shared.card>
    </div>
@endsection
