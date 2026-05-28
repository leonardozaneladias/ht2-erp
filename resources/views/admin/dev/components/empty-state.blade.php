@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.empty-state',
    'description' => 'Estados vazios reutilizáveis para listagens, filtros e seções sem permissão.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <x-shared.card title="Variações principais" subtitle="sm, md e lg com CTA opcional">
            <div class="grid gap-6">
                <div class="border-default-300 bg-card rounded-2xl border border-dashed">
                    <x-shared.empty-state
                        icon="tabler--file-text"
                        title="Nenhum contrato cadastrado"
                        description="Comece criando seu primeiro contrato para vincular formandos e abrir o fluxo de adesão."
                    >
                        <x-slot:action>
                            <x-shared.button variant="primary" icon="tabler--plus">Novo contrato</x-shared.button>
                        </x-slot:action>
                    </x-shared.empty-state>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="border-default-300 bg-card rounded-2xl border border-dashed">
                        <x-shared.empty-state
                            size="sm"
                            icon="tabler--search-off"
                            title="Nada encontrado"
                            description="Tente ajustar os filtros ou revisar o termo informado."
                        >
                            <x-slot:action>
                                <x-shared.button variant="default" appearance="outline">Limpar filtros</x-shared.button>
                            </x-slot:action>
                        </x-shared.empty-state>
                    </div>

                    <div class="border-default-300 bg-card rounded-2xl border border-dashed">
                        <x-shared.empty-state size="lg" icon="tabler--lock" title="Sem permissão">
                            Você não tem acesso a esta área. Fale com um administrador para liberar o perfil correto.
                        </x-shared.empty-state>
                    </div>
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Aba vazia da ficha do formando">
            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                <div class="border-default-300 mb-4 flex items-center justify-between border-b pb-3">
                    <div>
                        <p class="text-body-color font-semibold">Termos aceitos</p>
                        <p class="text-default-400 text-sm">Histórico jurídico do formando</p>
                    </div>

                    <x-shared.badge variant="default" pill>0 registros</x-shared.badge>
                </div>

                <x-shared.empty-state
                    size="sm"
                    icon="tabler--file-off"
                    title="Sem termos aceitos"
                    description="Os aceites do portal aparecerão aqui assim que o aluno concluir a assinatura digital."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
