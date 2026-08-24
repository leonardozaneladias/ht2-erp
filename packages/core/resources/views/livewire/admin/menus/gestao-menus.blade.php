<div
    x-data="{
        grupoSecao: '',
        busca: '',
        colapsadas: {},
        alternar(key) {
            this.colapsadas[key] = !this.colapsadas[key];
        },
        colapsada(key) {
            // Busca ativa expande tudo para mostrar os resultados.
            return this.busca.trim() === '' && this.colapsadas[key] === true;
        },
        casaOuPaiCasa(el) {
            const termo = this.busca.trim().toLowerCase();
            if (termo === '') return true;
            if ((el.dataset.busca || '').includes(termo)) return true;
            const grupo = el.closest('[data-tipo=grupo]');
            return grupo !== null && (grupo.dataset.busca || '').includes(termo);
        },
        grupoCasa(el) {
            const termo = this.busca.trim().toLowerCase();
            if (termo === '') return true;
            if ((el.dataset.busca || '').includes(termo)) return true;
            return Array.from(el.querySelectorAll('[data-busca]')).some((linha) =>
                (linha.dataset.busca || '').includes(termo),
            );
        },
    }"
    x-on:menus-abrir-editor.window="window.HSOverlay?.open(document.querySelector('#drawer-menu-editor'));"
    x-on:menus-fechar-editor.window="window.HSOverlay?.close(document.querySelector('#drawer-menu-editor'));"
    x-on:menus-fechar-modais.window="
        window.HSOverlay?.close(document.querySelector('#modal-menu-secao'));
        window.HSOverlay?.close(document.querySelector('#modal-menu-grupo'));
    "
>
    <x-admin.page-header
        title="Gestão de menus"
        subtitle="Arraste para organizar seções, grupos e itens; renomeie, troque ícones e controle a visibilidade do menu lateral."
    >
        <x-slot:actions>
            <x-shared.button variant="default" icon="tabler--layout-list" data-hs-overlay="#modal-menu-secao">
                Nova seção
            </x-shared.button>

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

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
        <div>
            <div class="[&_.mb-4]:mb-0 mb-4 max-w-sm">
                <x-shared.input
                    type="search"
                    name="busca-menu"
                    icon="tabler--search"
                    x-model.debounce.150ms="busca"
                    placeholder="Buscar item do menu..."
                    aria-label="Buscar item do menu"
                />
            </div>

            <p
                x-show="busca.trim() !== ''"
                x-cloak
                class="text-default-400 -mt-2 mb-3 text-xs"
            >Arrastar fica desativado durante a busca.</p>

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
                                        aria-hidden="true"
                                        x-bind:class="busca.trim() !== '' ? 'pointer-events-none opacity-30' : ''"
                                    ></i>

                                    <button
                                        type="button"
                                        class="text-default-400 hover:bg-light hover:text-body-color flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg transition-colors duration-200"
                                        aria-label="Recolher ou expandir a seção {{ $secao['title'] }}"
                                        x-on:click="alternar('secao-{{ $secao['key'] }}')"
                                    >
                                        <i
                                            class="iconify tabler--chevron-down text-base transition-transform duration-200"
                                            aria-hidden="true"
                                            x-bind:class="colapsada('secao-{{ $secao['key'] }}') ? '-rotate-90' : ''"
                                        ></i>
                                    </button>

                                    <div class="min-w-0">
                                        <h4 class="card-title">
                                            {{ $secao['title'] }}
                                            <span class="text-default-400 text-xs font-normal">
                                                ({{ count($secao['items']) }})
                                            </span>
                                        </h4>

                                        @if ($secao['personalizado'] && ! $secao['eCustom'] && $secao['title'] !== $secao['titlePadrao'])
                                            <p class="text-2xs text-default-400 mt-1">Padrão: {{ $secao['titlePadrao'] }}</p>
                                        @endif
                                    </div>

                                    @if ($secao['eCustom'])
                                        <x-shared.badge variant="secondary" size="sm">Criada aqui</x-shared.badge>
                                    @elseif ($secao['personalizado'])
                                        <x-shared.badge variant="info" size="sm">Personalizada</x-shared.badge>
                                    @endif
                                </div>
                            </x-slot:header>

                            <x-slot:headerActions>
                                <x-shared.button
                                    variant="default"
                                    appearance="ghost"
                                    size="sm"
                                    icon="tabler--folder-plus"
                                    x-on:click="grupoSecao = '{{ $secao['key'] }}'; window.HSOverlay?.open(document.querySelector('#modal-menu-grupo'))"
                                >
                                    Grupo
                                </x-shared.button>

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
                                :container-id="'secao:' . $secao['key']"
                                :wire-ignore="false"
                                fallback-on-body
                                swap-threshold="0.65"
                                class="min-h-12"
                                x-show="! colapsada('secao-{{ $secao['key'] }}')"
                            >
                                @forelse ($secao['items'] as $entry)
                                    @if ($entry['tipo'] === 'grupo')
                                        <div
                                            wire:key="menu-grupo-{{ $entry['key'] }}"
                                            data-id="{{ $entry['key'] }}"
                                            data-tipo="grupo"
                                            data-busca="{{ mb_strtolower($entry['label']) }}"
                                            x-show="grupoCasa($el)"
                                            @class ([
                                        'border-default-200 bg-light/40 my-1.5 rounded-lg border',
                                        'opacity-60' => ! $entry['ativo'],
                                    ])
                                        >
                                            <div class="flex items-center gap-2.5 px-2 py-2">
                                                <i
                                                    class="iconify tabler--grip-vertical drag-handle text-default-400 shrink-0 cursor-grab text-lg"
                                                    aria-hidden="true"
                                                    x-bind:class="
                                                        busca.trim() !== '' ? 'pointer-events-none opacity-30' : ''
                                                    "
                                                ></i>

                                                <button
                                                    type="button"
                                                    class="text-default-400 hover:bg-light hover:text-body-color flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-lg transition-colors duration-200"
                                                    aria-label="Recolher ou expandir o grupo {{ $entry['label'] }}"
                                                    x-on:click="alternar('grupo-{{ $entry['key'] }}')"
                                                >
                                                    <i
                                                        class="iconify tabler--chevron-down text-sm transition-transform duration-200"
                                                        aria-hidden="true"
                                                        x-bind:class="colapsada('grupo-{{ $entry['key'] }}') ? '-rotate-90' : ''"
                                                    ></i>
                                                </button>

                                                <span
                                                    class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-lg"
                                                >
                                                    <i
                                                        class="iconify {{ $entry['icon'] }} text-base"
                                                        aria-hidden="true"
                                                    ></i>
                                                </span>

                                                <div class="min-w-0 grow">
                                                    <p class="text-body-color truncate text-sm font-semibold">
                                                        {{ $entry['label'] }}
                                                        <span class="text-default-400 text-xs font-normal">
                                                            ({{ count($entry['children']) }} {{ count($entry['children']) === 1 ? 'item' : 'itens' }})
                                                        </span>
                                                    </p>
                                                    <p class="text-default-400 truncate text-xs">Grupo — abre como submenu na sidebar</p>
                                                </div>

                                                <div class="flex shrink-0 items-center gap-1.5">
                                                    @unless ($entry['ativo'])
                                                        <x-shared.badge
                                                            variant="danger"
                                                            size="sm"
                                                            icon="tabler--eye-off"
                                                        >
                                                            Inativo
                                                        </x-shared.badge>
                                                    @endunless

                                                    <x-shared.button
                                                        variant="default"
                                                        appearance="ghost"
                                                        size="sm"
                                                        :icon="$entry['ativo'] ? 'tabler--eye' : 'tabler--eye-off'"
                                                        icon-only
                                                        aria-label="{{ $entry['ativo'] ? 'Desativar' : 'Reativar' }} grupo {{ $entry['label'] }}"
                                                        wire:click="alternarAtivoGrupo('{{ $entry['key'] }}')"
                                                    />

                                                    <x-shared.button
                                                        variant="default"
                                                        appearance="ghost"
                                                        size="sm"
                                                        icon="tabler--pencil"
                                                        icon-only
                                                        aria-label="Editar grupo {{ $entry['label'] }}"
                                                        wire:click="editar('grupo', '{{ $entry['key'] }}')"
                                                    />
                                                </div>
                                            </div>

                                            <x-admin.sortable-list
                                                id="menu-grupo-{{ $entry['key'] }}"
                                                target="reordenarItens"
                                                group="menu-itens"
                                                :container-id="'grupo:' . $entry['key']"
                                                handle=".drag-handle-filho"
                                                :put-reject="['grupo']"
                                                :wire-ignore="false"
                                                fallback-on-body
                                                swap-threshold="0.65"
                                                class="border-default-200 ms-9 min-h-10 border-s ps-1 pb-1"
                                                x-show="! colapsada('grupo-{{ $entry['key'] }}')"
                                            >
                                                @forelse ($entry['children'] as $filho)
                                                    @include ('livewire.admin.menus.partials.linha-item', [
                                                'item' => $filho,
                                                'handle' => 'drag-handle-filho',
                                            ])
                                                @empty
                                                    <p class="text-default-400 px-2 py-3 text-center text-xs">Arraste itens para este grupo.</p>
                                                @endforelse
                                            </x-admin.sortable-list>
                                        </div>
                                    @else
                                        @include ('livewire.admin.menus.partials.linha-item', [
                                    'item' => $entry,
                                    'handle' => 'drag-handle',
                                ])
                                    @endif
                                @empty
                                    <p class="text-default-400 px-2 py-4 text-center text-sm">Arraste itens ou grupos para esta seção.</p>
                                @endforelse
                            </x-admin.sortable-list>
                        </x-shared.card>
                    </div>
                @endforeach
            </x-admin.sortable-list>
        </div>

        <div class="hidden xl:block">
            @include ('livewire.admin.menus.partials.preview-sidebar')
        </div>
    </div>

    {{-- Modal: nova seção --}}
    <x-shared.modal id="modal-menu-secao" title="Nova seção" size="sm" wire:ignore.self>
        <div class="space-y-4">
            <x-shared.input
                name="novaSecaoLabel"
                label="Nome da seção"
                wire:model="novaSecaoLabel"
                wire:keydown.enter="criarSecao"
                maxlength="60"
                required
            />

            <div class="flex justify-end gap-2">
                <x-shared.button variant="default" data-hs-overlay="#modal-menu-secao"> Cancelar</x-shared.button>
                <x-shared.button variant="primary" icon="tabler--plus" wire:click="criarSecao">
                    Criar seção
                </x-shared.button>
            </div>
        </div>
    </x-shared.modal>

    {{-- Modal: novo grupo (a seção destino vem do botão que abriu) --}}
    <x-shared.modal id="modal-menu-grupo" title="Novo grupo (submenu)" size="md" wire:ignore.self>
        <div class="space-y-4">
            <x-shared.alert variant="info">
                O grupo abre como submenu na sidebar e só aparece quando tem itens visíveis ao usuário. Depois de criar,
                arraste itens para dentro dele.
            </x-shared.alert>

            <x-shared.input
                name="novoGrupoLabel"
                label="Nome do grupo"
                wire:model="novoGrupoLabel"
                maxlength="60"
                required
            />

            <div>
                <p class="text-body-color mb-2 text-sm font-medium">Ícone</p>

                <div class="mb-3 flex items-center gap-3">
                    <span
                        class="bg-light text-default-600 flex size-10 shrink-0 items-center justify-center rounded-lg"
                    >
                        <i class="iconify {{ $novoGrupoIcone }} text-xl" aria-hidden="true"></i>
                    </span>
                    <p class="text-default-400 truncate text-xs">{{ $novoGrupoIcone }}</p>
                </div>

                @include ('livewire.admin.menus.partials.grade-icones', [
                    'modelo' => 'novoGrupoIcone',
                    'selecionado' => $novoGrupoIcone,
                ])

                @error ('novoGrupoIcone')
                    <p class="text-danger mt-2 text-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <x-shared.button variant="default" data-hs-overlay="#modal-menu-grupo"> Cancelar</x-shared.button>
                <x-shared.button variant="primary" icon="tabler--plus" x-on:click="$wire.criarGrupo(grupoSecao);">
                    Criar grupo
                </x-shared.button>
            </div>
        </div>
    </x-shared.modal>

    {{-- Drawer de edição (item, grupo ou seção) --}}
    <x-admin.drawer
        id="drawer-menu-editor"
        size="lg"
        :title="match ($editandoTipo) {
            'secao' => 'Editar seção',
            'grupo' => 'Editar grupo',
            default => 'Editar item do menu',
        }"
        wire:ignore.self
    >
        @if ($editandoKey !== null)
            <div class="space-y-6">
                <x-shared.input
                    name="label"
                    label="Nome exibido"
                    wire:model="label"
                    maxlength="80"
                    :hint="$editandoEhCustom ? null : 'Deixe igual ao padrão para voltar a herdar o nome do módulo.'"
                />

                @if (in_array($editandoTipo, ['item', 'grupo'], true))
                    <div>
                        <p class="text-body-color mb-2 text-sm font-medium">Ícone</p>

                        <div class="mb-3 flex items-center gap-3">
                            <span
                                class="bg-light text-default-600 flex size-10 shrink-0 items-center justify-center rounded-lg"
                            >
                                <i class="iconify {{ $icone }} text-xl" aria-hidden="true"></i>
                            </span>
                            <p class="text-default-400 truncate text-xs">{{ $icone }}</p>
                        </div>

                        @include ('livewire.admin.menus.partials.grade-icones', [
                            'modelo' => 'icone',
                            'selecionado' => $icone,
                        ])

                        @error ('icone')
                            <p class="text-danger mt-2 text-xs">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-shared.toggle
                        name="ativo"
                        :label="$editandoTipo === 'grupo' ? 'Grupo ativo no menu' : 'Item ativo no menu'"
                        wire:model="ativo"
                        :hint="$editandoTipo === 'grupo'
                            ? 'Desativado, o grupo e os itens dele somem do menu para todos (o acesso às páginas continua regido pelo ACL).'
                            : 'Desativado, some do menu para todos os usuários (o acesso às páginas continua regido pelo ACL).'"
                    />
                @endif

                <div class="flex items-center justify-between gap-2">
                    @if ($editandoEhCustom)
                        <x-shared.button
                            variant="danger"
                            appearance="outline"
                            size="sm"
                            icon="tabler--trash"
                            wire:click="solicitarExcluirCustom('{{ $editandoTipo }}', '{{ $editandoKey }}')"
                        >
                            Excluir {{ $editandoTipo === 'grupo' ? 'grupo' : 'seção' }}
                        </x-shared.button>
                    @else
                        <x-shared.button
                            variant="default"
                            size="sm"
                            icon="tabler--restore"
                            wire:click="solicitarRestaurar('{{ $editandoTipo }}', '{{ $editandoKey }}')"
                        >
                            Restaurar padrão
                        </x-shared.button>
                    @endif

                    <x-shared.button variant="primary" icon="tabler--device-floppy" wire:click="salvarEdicao">
                        Salvar
                    </x-shared.button>
                </div>

                @if ($editandoTipo === 'grupo')
                    <x-shared.alert variant="info">
                        Grupos não têm permissão própria: a visibilidade é herdada dos itens — o grupo só aparece para
                        quem enxerga pelo menos um item dele.
                    </x-shared.alert>
                @endif

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
                                    @php ($podeEditarPerfil = ! $protegida && app(\HT2ML\Core\Policies\RolePolicy::class)->update(auth('admin')->user(), $perfil))
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
