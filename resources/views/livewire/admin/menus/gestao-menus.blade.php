<div
    x-data
    x-on:menus-abrir-editor.window="window.HSOverlay?.open(document.querySelector('#drawer-menu-editor'));"
    x-on:menus-fechar-editor.window="window.HSOverlay?.close(document.querySelector('#drawer-menu-editor'));"
>
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

            <div class="mt-3">
                <x-shared.button
                    variant="warning"
                    appearance="outline"
                    size="sm"
                    icon="tabler--trash"
                    wire:click="solicitarLimparOrfas"
                >
                    Limpar órfãs
                </x-shared.button>
            </div>
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

                    <x-slot:headerActions>
                        <x-shared.button
                            variant="default"
                            appearance="ghost"
                            size="sm"
                            icon="tabler--pencil"
                            icon-only
                            aria-label="Editar seção {{ $secao['title'] }}"
                            wire:click="editar('secao', '{{ $secao['key'] }}')"
                        />
                    </x-slot:headerActions>

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
                                            Inativo
                                        </x-shared.badge>
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

                                    <x-shared.button
                                        variant="default"
                                        appearance="ghost"
                                        size="sm"
                                        :icon="$item['ativo'] ? 'tabler--eye' : 'tabler--eye-off'"
                                        icon-only
                                        aria-label="{{ $item['ativo'] ? 'Desativar' : 'Reativar' }} item {{ $item['label'] }}"
                                        wire:click="alternarAtivo('{{ $item['key'] }}')"
                                    />

                                    <x-shared.button
                                        variant="default"
                                        appearance="ghost"
                                        size="sm"
                                        icon="tabler--pencil"
                                        icon-only
                                        aria-label="Editar item {{ $item['label'] }}"
                                        wire:click="editar('item', '{{ $item['key'] }}')"
                                    />
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

    <x-admin.drawer
        id="drawer-menu-editor"
        size="lg"
        :title="$editandoTipo === 'secao' ? 'Editar seção' : 'Editar item do menu'"
        wire:ignore.self
    >
        @if ($editandoKey !== null)
            <div class="space-y-6">
                <x-shared.input
                    name="label"
                    label="Nome exibido"
                    wire:model="label"
                    maxlength="80"
                    hint="Deixe igual ao padrão para voltar a herdar o nome do módulo."
                />

                @if ($editandoTipo === 'item')
                    <div>
                        <p class="text-body-color mb-2 text-sm font-medium">Ícone</p>

                        <div class="mb-3 flex items-center gap-3">
                            <span
                                class="bg-light text-default-600 flex size-10 shrink-0 items-center justify-center rounded-lg"
                            >
                                <i class="iconify {{ $icone }} text-xl"></i>
                            </span>
                            <p class="text-default-400 truncate text-xs">{{ $icone }}</p>
                        </div>

                        <div class="grid grid-cols-8 gap-1.5">
                            @foreach (\App\Support\Menu\IconesMenu::disponiveis() as $opcao)
                                <button
                                    type="button"
                                    wire:click="$set('icone', '{{ $opcao }}')"
                                    title="{{ $opcao }}"
                                    @class ([
                                        'border-default-200 hover:border-primary/60 hover:bg-light flex size-9 items-center justify-center rounded-lg border transition',
                                        'border-primary bg-primary/10 text-primary' => $icone === $opcao,
                                        'text-default-600' => $icone !== $opcao,
                                    ])
                                >
                                    <i class="iconify {{ $opcao }} text-lg"></i>
                                </button>
                            @endforeach
                        </div>

                        @error ('icone')
                            <p class="text-danger mt-2 text-xs">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-shared.toggle
                        name="ativo"
                        label="Item ativo no menu"
                        wire:model="ativo"
                        hint="Desativado, some do menu para todos os usuários (o acesso às páginas continua regido pelo ACL)."
                    />
                @endif

                <div class="flex items-center justify-between gap-2">
                    <x-shared.button
                        variant="default"
                        size="sm"
                        icon="tabler--restore"
                        wire:click="solicitarRestaurar('{{ $editandoTipo }}', '{{ $editandoKey }}')"
                    >
                        Restaurar padrão
                    </x-shared.button>

                    <x-shared.button variant="primary" icon="tabler--device-floppy" wire:click="salvarEdicao">
                        Salvar
                    </x-shared.button>
                </div>

                @if ($editandoTipo === 'item')
                    <div class="border-default-200 border-t pt-5">
                        <h5 class="text-body-color text-sm font-semibold">Visibilidade por perfil</h5>

                        @php ($permissaoItem = $this->permissaoDoItemEditado())

                        @if ($permissaoItem === null)
                            <p class="text-default-500 mt-2 text-sm">Este item não exige permissão — é visível para todos os perfis.</p>
                        @else
                            <x-shared.alert variant="warning" class="mt-3">
                                Este controle concede/revoga a permissão
                                <code class="font-mono text-xs">{{ $permissaoItem }}</code>
                                no perfil — afeta o menu <strong>e</strong> o acesso às páginas do módulo.
                            </x-shared.alert>
                            <ul class="divide-default-200 mt-2 divide-y">
                                @foreach ($this->perfis as $perfil)
                                    @php ($protegida = in_array($perfil->name, (array) config('access.protected_roles', []), true))
                                    @php ($podeEditarPerfil = ! $protegida && app(\App\Policies\RolePolicy::class)->update(auth('admin')->user(), $perfil))
                                    @php ($temPermissao = $perfil->hasPermissionTo($permissaoItem))
                                    <li
                                        wire:key="menu-perfil-{{ $perfil->id }}"
                                        class="flex items-center justify-between gap-3 py-2.5"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-body-color truncate text-sm font-medium">
                                                {{ $perfil->name }}
                                            </p>

                                            @if (filled($perfil->descricao))
                                                <p class="text-default-400 truncate text-xs">{{ $perfil->descricao }}</p>
                                            @endif
                                        </div>

                                        @if ($protegida)
                                            <x-shared.badge variant="success" size="sm" icon="tabler--eye">
                                                Sempre visível
                                            </x-shared.badge>
                                        @else
                                            <x-shared.button
                                                :variant="$temPermissao ? 'success' : 'default'"
                                                appearance="soft"
                                                size="sm"
                                                :icon="$temPermissao ? 'tabler--eye' : 'tabler--eye-off'"
                                                :disabled="! $podeEditarPerfil"
                                                :title="$podeEditarPerfil ? null : 'Sem hierarquia para gerir este perfil'"
                                                wire:click="alternarPerfil({{ $perfil->id }})"
                                            >
                                                {{ $temPermissao ? 'Visível' : 'Oculto' }}
                                            </x-shared.button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </x-admin.drawer>
</div>
