<div>
    <x-admin.page-header
        title="Gestão de menus"
        subtitle="Arraste para reordenar seções e itens, renomeie, troque ícones e controle a visibilidade do menu lateral."
    >
        <x-slot:actions>
            <x-shared.button variant="default" icon="tabler--restore" wire:click="solicitarRestaurarTudo">
                Restaurar padrão
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    @php ($gestao = $this->estrutura())

    @if ($gestao['orfas']->isNotEmpty())
        <x-shared.alert variant="warning" title="Personalizações órfãs" class="mb-5">
            {{ $gestao['orfas']->count() }} {{ $gestao['orfas']->count() === 1 ? 'personalização aponta' : 'personalizações apontam' }} para
            itens que não existem mais no registro do menu ({{ $gestao['orfas']->pluck('key')->join(', ') }}). Elas são
            ignoradas na renderização.
        </x-shared.alert>
    @endif

    <x-admin.sortable-list
        id="menu-secoes"
        target="reordenarSecoes"
        handle=".secao-drag-handle"
        :wire-ignore="false"
        class="space-y-5"
    >
        @foreach ($gestao['secoes'] as $secao)
            <div wire:key="menu-secao-{{ $secao['key'] }}" data-id="{{ $secao['key'] }}">
                <x-shared.card>
                    <x-slot:header>
                        <div class="flex min-w-0 items-center gap-3">
                            <i
                                class="iconify tabler--grip-vertical secao-drag-handle text-default-400 shrink-0 cursor-grab text-xl"
                                title="Arraste para reordenar a seção"
                            ></i>

                            <div class="min-w-0">
                                <h4 class="card-title">{{ $secao['title'] }}</h4>

                                @if ($secao['personalizado'] && $secao['title'] !== $secao['titlePadrao'])
                                    <p class="text-2xs text-default-400 mt-1">Padrão: {{ $secao['titlePadrao'] }}</p>
                                @endif
                            </div>

                            @if ($secao['personalizado'])
                                <x-shared.badge variant="info" size="sm">Personalizada</x-shared.badge>
                            @endif
                        </div>
                    </x-slot:header>

                    <x-admin.sortable-list
                        id="menu-itens-{{ $secao['key'] }}"
                        target="reordenarItens"
                        group="menu-itens"
                        :container-id="$secao['key']"
                        :wire-ignore="false"
                        class="min-h-12"
                    >
                        @forelse ($secao['items'] as $item)
                            <div
                                wire:key="menu-item-{{ $item['key'] }}"
                                data-id="{{ $item['key'] }}"
                                @class ([
                                    'border-default-200 flex items-center gap-3 border-b px-2 py-3 last:border-b-0',
                                    'opacity-60' => ! $item['ativo'],
                                ])
                            >
                                <i
                                    class="iconify tabler--grip-vertical drag-handle text-default-400 shrink-0 cursor-grab text-lg"
                                ></i>

                                <span
                                    class="bg-light text-default-600 flex size-9 shrink-0 items-center justify-center rounded-lg"
                                >
                                    <i class="iconify {{ $item['icon'] }} text-lg"></i>
                                </span>

                                <div class="min-w-0 grow">
                                    <p class="text-body-color truncate text-sm font-semibold">
                                        {{ $item['label'] }}

                                        @if ($item['personalizado'] && $item['label'] !== $item['labelPadrao'])
                                            <span class="text-default-400 text-xs font-normal">
                                                (padrão: {{ $item['labelPadrao'] }})
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-default-400 mt-0.5 truncate text-xs">{{ $item['route'] }}</p>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                    @unless ($item['ativo'])
                                        <x-shared.badge variant="danger" size="sm" icon="tabler--eye-off">
                                            Inativo</x-shared.badge
                                        >
                                    @endunless

                                    @if ($item['personalizado'])
                                        <x-shared.badge variant="info" size="sm">Personalizado</x-shared.badge>
                                    @endif

                                    @if (($item['permission'] ?? null) !== null)
                                        <x-shared.badge variant="default" size="sm" icon="tabler--lock">
                                            {{ $item['permission'] }}
                                        </x-shared.badge>
                                    @else
                                        <x-shared.badge variant="success" size="sm" icon="tabler--world">
                                            Visível para todos
                                        </x-shared.badge>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-default-400 px-2 py-4 text-center text-sm">Arraste itens para esta seção.</p>
                        @endforelse
                    </x-admin.sortable-list>
                </x-shared.card>
            </div>
        @endforeach
    </x-admin.sortable-list>
</div>
