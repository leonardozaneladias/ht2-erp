<div class="space-y-6">
    <x-admin.page-header
        title="Controle de acesso"
        subtitle="Perfis, permissões e acessos das pessoas — tudo num lugar só."
    />

    @php
        $lentes = ['perfis' => ['Perfis', 'tabler--shield-lock']];
        if ($podeVerPessoas) {
            $lentes['pessoas'] = ['Pessoas', 'tabler--users'];
        }
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
        {{-- Lista lateral (master) --}}
        <aside class="lg:w-80 lg:shrink-0">
            <div class="bg-card border-default-200 overflow-hidden rounded-lg border">
                {{-- Seletor de lente --}}
                @if (count($lentes) > 1)
                    <div class="border-default-200 border-b p-3">
                        <div class="bg-default-100 flex gap-1 rounded-lg p-1">
                            @foreach ($lentes as $valor => [$rotulo, $icone])
                                <button
                                    type="button"
                                    wire:click="trocarLente('{{ $valor }}')"
                                    @class ([
                                        'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition',
                                        'bg-card text-primary shadow-sm' => ! $mostrarHistorico && $lente === $valor,
                                        'text-default-600 hover:text-default-900' => $mostrarHistorico || $lente !== $valor,
                                    ])
                                >
                                    <span class="iconify {{ $icone }} me-1 size-4 align-middle"></span>
                                    {{ $rotulo }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Busca --}}
                <div class="border-default-200 border-b p-3">
                    <div class="relative">
                        <span
                            class="iconify tabler--search text-default-400 absolute start-3 top-1/2 size-4 -translate-y-1/2"
                        ></span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="busca"
                            placeholder="{{ $lente === 'perfis' ? 'Buscar perfil...' : 'Buscar pessoa...' }}"
                            class="form-input ps-9"
                            aria-label="Buscar na lista"
                        />
                    </div>
                </div>

                {{-- Itens --}}
                <div class="max-h-[55vh] space-y-1 overflow-y-auto p-2" wire:loading.class="opacity-50">
                    @forelse ($itens as $item)
                        <button
                            type="button"
                            wire:key="{{ $lente }}-{{ $item->id }}"
                            wire:click="selecionar({{ $item->id }})"
                            @class ([
                                'flex w-full items-center justify-between gap-2 rounded-md px-3 py-2 text-start transition',
                                'bg-primary/10 text-primary' => ! $modoNovo && ! $mostrarHistorico && $selecionadoId === $item->id,
                                'hover:bg-default-100' => $modoNovo || $mostrarHistorico || $selecionadoId !== $item->id,
                            ])
                        >
                            @if ($lente === 'perfis')
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium">{{ $item->name }}</span>
                                    @if ($item->descricao)
                                        <span class="text-default-500 block truncate text-xs">
                                            {{ $item->descricao }}
                                        </span>
                                    @endif
                                </span>
                                <span class="text-default-400 shrink-0 text-xs">nível {{ $item->nivel }}</span>
                            @else
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium">{{ $item->nome }}</span>
                                    <span class="text-default-500 block truncate text-xs">{{ $item->email }}</span>
                                </span>
                                @unless ($item->ativo)
                                    <span class="text-default-400 shrink-0 text-xs">inativo</span>
                                @endunless
                            @endif
                        </button>
                    @empty
                        <div class="p-4">
                            <x-shared.empty-state
                                :icon="$lente === 'perfis' ? 'tabler--shield-off' : 'tabler--user-off'"
                                title="Nada encontrado"
                                description="Ajuste a busca para localizar o que procura."
                            />
                        </div>
                    @endforelse
                </div>

                {{-- Rodapé: criar e ferramentas --}}
                @if (($lente === 'perfis' && $podeCriarPerfil) || $podeVerHistorico)
                    <div class="border-default-200 space-y-2 border-t p-3">
                        @if ($lente === 'perfis' && $podeCriarPerfil)
                            <button
                                type="button"
                                wire:click="novoPerfil"
                                @class ([
                                    'btn btn-sm w-full',
                                    'btn-primary' => $modoNovo,
                                    'btn-outline-primary' => ! $modoNovo,
                                ])
                            >
                                <span class="iconify tabler--plus me-1 size-4"></span>
                                Novo perfil
                            </button>
                        @endif

                        @if ($podeVerHistorico)
                            <button
                                type="button"
                                wire:click="verHistorico"
                                @class ([
                                    'flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition',
                                    'bg-primary/10 text-primary' => $mostrarHistorico,
                                    'text-default-600 hover:bg-default-100' => ! $mostrarHistorico,
                                ])
                            >
                                <span class="iconify tabler--history size-4"></span>
                                Histórico de acesso
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </aside>

        {{-- Painel de detalhe --}}
        <section class="min-w-0 flex-1">
            @if ($mostrarHistorico)
                <livewire:admin.acesso.painel-historico :key="'painel-historico'" />
            @elseif ($lente === 'perfis' && ($modoNovo || $selecionadoId !== null))
                <livewire:admin.acesso.painel-perfil
                    :perfil-id="$modoNovo ? null : $selecionadoId"
                    :key="'painel-perfil-' . ($modoNovo ? 'novo' : $selecionadoId)"
                />
            @elseif ($lente === 'pessoas' && $selecionadoId !== null)
                <livewire:admin.acesso.painel-pessoa
                    :usuario-id="$selecionadoId"
                    :key="'painel-pessoa-' . $selecionadoId"
                />
            @else
                <div class="bg-card border-default-200 rounded-lg border p-8">
                    <x-shared.empty-state
                        icon="tabler--hand-click"
                        :title="$lente === 'perfis' ? 'Selecione um perfil' : 'Selecione uma pessoa'"
                        description="Escolha um item na lista ao lado, ou crie um novo."
                    />
                </div>
            @endif
        </section>
    </div>
</div>
