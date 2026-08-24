@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.collapse',
    'description' => 'Bloco expansível para filtros avançados, detalhes sob demanda e seções compactas em cards.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Trigger interno" subtitle="Slot de trigger + conteúdo">
            <x-shared.collapse id="collapse-detalhes">
                <x-slot:trigger>
                    <button
                        type="button"
                        class="hs-collapse-toggle btn bg-light text-dark hover:text-primary"
                        data-hs-collapse="#collapse-detalhes"
                        aria-controls="collapse-detalhes"
                        aria-expanded="false"
                    >
                        Ver detalhes
                        <i
                            class="iconify tabler--chevron-down hs-collapse-open:rotate-180 text-base transition-transform"
                        ></i>
                    </button>
                </x-slot:trigger>

                <div
                    class="border-default-300 bg-light/40 text-default-400 mt-3 rounded-lg border border-dashed p-4 text-sm"
                >
                    Histórico resumido de cobranças, ações automáticas e observações lançadas pela equipe financeira.
                </div>
            </x-shared.collapse>
        </x-shared.card>

        <x-shared.card title="Filtros colapsáveis" subtitle="Exemplo mais próximo das telas 14.12 e 14.13">
            <div class="mb-4">
                <button
                    type="button"
                    class="hs-collapse-toggle btn bg-primary hover:bg-primary-hover text-white"
                    data-hs-collapse="#collapse-filtros"
                    aria-controls="collapse-filtros"
                    aria-expanded="true"
                >
                    <i class="iconify tabler--filter text-base"></i>
                    Filtros avançados
                    <i
                        class="iconify tabler--chevron-down hs-collapse-open:rotate-180 text-base transition-transform"
                    ></i>
                </button>
            </div>

            <x-shared.collapse id="collapse-filtros" open>
                <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="border-default-300 bg-card text-default-400 rounded border px-3 py-2 text-sm">
                            Contrato
                        </div>
                        <div class="border-default-300 bg-card text-default-400 rounded border px-3 py-2 text-sm">
                            Curso
                        </div>
                        <div class="border-default-300 bg-card text-default-400 rounded border px-3 py-2 text-sm">
                            Período
                        </div>
                        <div class="border-default-300 bg-card text-default-400 rounded border px-3 py-2 text-sm">
                            Adimplência
                        </div>
                    </div>
                </div>
            </x-shared.collapse>
        </x-shared.card>
    </div>
@endsection
