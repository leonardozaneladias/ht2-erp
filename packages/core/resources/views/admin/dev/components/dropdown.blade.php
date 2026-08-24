@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.dropdown',
    'description' => 'Menu de ações compacto para linhas de tabela, toolbars e user menu com dependência explícita de Preline.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Ações por linha" subtitle="Padrão de tabela admin">
            <div class="space-y-3">
                @foreach ([
                    ['ADM-2026-01', 'Ativo', 'success'],
                    ['ADM-2026-02', 'Sem reajuste', 'warning'],
                    ['ADM-2026-03', 'Bloqueado', 'danger'],
                ] as [$codigo, $status, $variant])
                    <div
                        class="border-default-300 bg-light/30 flex items-center justify-between rounded-lg border px-4 py-3"
                    >
                        <div>
                            <p class="text-body-color font-medium">{{ $codigo }}</p>
                            <p class="text-default-400 text-sm">Instituição Exemplo • 2026</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-shared.badge :variant="$variant" pill>{{ $status }}</x-shared.badge>

                            <x-shared.dropdown placement="bottom-end">
                                <x-slot:button>
                                    <button
                                        type="button"
                                        class="hs-dropdown-toggle btn btn-icon btn-sm bg-light text-dark hover:text-primary"
                                        aria-label="Abrir ações"
                                    >
                                        <i class="iconify tabler--dots-vertical text-base"></i>
                                    </button>
                                </x-slot:button>

                                <x-shared.dropdown-item icon="tabler--eye" :href="'#!'">
                                    Ver detalhes</x-shared.dropdown-item
                                >
                                <x-shared.dropdown-item icon="tabler--edit" :href="'#!'">Editar</x-shared.dropdown-item>
                                <x-shared.dropdown-item icon="tabler--copy">Duplicar</x-shared.dropdown-item>
                                <x-shared.dropdown-divider />
                                <x-shared.dropdown-item icon="tabler--ban" variant="danger">
                                    Inativar</x-shared.dropdown-item
                                >
                            </x-shared.dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="User menu" subtitle="Exemplo para topbar">
            <div class="flex items-center justify-end">
                <x-shared.dropdown placement="bottom-end" auto-close="inside">
                    <x-slot:button>
                        <button
                            type="button"
                            class="hs-dropdown-toggle border-default-300 bg-card hover:bg-light inline-flex items-center gap-2 rounded-full border px-3 py-2"
                            aria-label="Abrir menu do usuário"
                        >
                            <span
                                class="bg-primary/15 text-primary inline-flex size-9 items-center justify-center rounded-full"
                            >
                                <i class="iconify tabler--user text-lg"></i>
                            </span>
                            <span class="text-body-color hidden text-sm font-medium sm:inline">Leonardo Dias</span>
                            <i
                                class="iconify tabler--chevron-down hs-dropdown-open:rotate-180 text-base transition-transform"
                            ></i>
                        </button>
                    </x-slot:button>

                    <x-shared.dropdown-item icon="tabler--user-circle" :href="'#!'">Meu perfil</x-shared.dropdown-item>
                    <x-shared.dropdown-item icon="tabler--settings" :href="'#!'">Configurações</x-shared.dropdown-item>
                    <x-shared.dropdown-divider />
                    <x-shared.dropdown-item icon="tabler--logout" variant="danger">Sair</x-shared.dropdown-item>
                </x-shared.dropdown>
            </div>
        </x-shared.card>
    </div>
@endsection
