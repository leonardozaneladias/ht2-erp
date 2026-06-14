<div class="space-y-4">
    {{-- Cabeçalho (sobre o fundo da seção, como em /admin/configuracoes) --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-lg font-semibold">{{ $modo === 'criar' ? 'Novo perfil' : $name }}</h3>
            <p class="text-default-500 mt-0.5 text-sm">
                @if ($modo === 'criar')
                    Defina nome, nível hierárquico e permissões.
                @else
                    Nível {{ $nivel }}
                    @unless ($podeEditar)
                        ·
                        <span class="text-warning font-medium">somente leitura</span>
                    @endunless
                @endif
            </p>
        </div>

        @unless ($podeEditar)
            <span class="badge badge-soft-warning shrink-0">
                <span class="iconify tabler--lock me-1 size-3.5"></span>
                Protegido
            </span>
        @endunless
    </div>

    {{-- Abas (ao editar) + corpo conectado (mesmo padrão de /admin/configuracoes) --}}
    <div>
        @if ($modo === 'editar')
            <x-shared.tab-nav>
                @foreach (['permissoes' => 'Permissões', 'membros' => 'Membros'] as $valor => $rotulo)
                    <x-shared.tab-trigger
                        :id="'perfil-' . $valor"
                        :preline="false"
                        :active="$valor === 'membros' ? $aba === 'membros' : $aba !== 'membros'"
                        wire:click="$set('aba', '{{ $valor }}')"
                    >
                        {{ $rotulo }}
                        @if ($valor === 'membros')
                            <span class="text-default-400">({{ $this->membros->count() }})</span>
                        @endif
                    </x-shared.tab-trigger>
                @endforeach
            </x-shared.tab-nav>
        @endif

        {{-- Superfície: card normal ao criar; card conectado às abas ao editar --}}
        <div @class (['card p-5', 'rounded-t-none border-t-0' => $modo === 'editar'])>
            @if ($aba === 'membros' && $modo === 'editar')
                {{-- ── Aba Membros ────────────────────────────────────────── --}}
                @forelse ($this->membros as $membro)
                    <div class="border-default-100 flex items-center gap-3 border-b py-2.5 last:border-0">
                        <span
                            class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                        >
                            {{ \Illuminate\Support\Str::of($membro->nome)->substr(0, 1)->upper() }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $membro->nome }}</p>
                            <p class="text-default-500 truncate text-xs">{{ $membro->email }}</p>
                        </div>
                    </div>
                @empty
                    <x-shared.empty-state
                        icon="tabler--users"
                        title="Nenhum membro"
                        description="Ninguém possui este perfil ainda. Atribua na lente Pessoas."
                    />
                @endforelse
            @else
                {{-- ── Aba Permissões ─────────────────────────────────────── --}}
                @if ($podeEditar)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <x-shared.input
                                name="name"
                                label="Nome do perfil"
                                placeholder="ex.: Gerente Financeiro"
                                wire:model.live.debounce.400ms="name"
                                required
                            />
                        </div>
                        <x-shared.input
                            name="nivel"
                            type="number"
                            label="Nível hierárquico"
                            hint="Maior = mais poder."
                            min="1"
                            max="999"
                            wire:model.live.debounce.400ms="nivel"
                            required
                        />
                    </div>
                    <div class="mt-4">
                        <x-shared.textarea
                            name="descricao"
                            label="Descrição (opcional)"
                            rows="2"
                            placeholder="Para que serve este perfil?"
                            wire:model.live.debounce.400ms="descricao"
                        />
                    </div>
                    <div class="mt-5 space-y-4">
                        @forelse ($permissoesAgrupadas as $grupo => $permissoes)
                            @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($grupo)?->label() ?? \Illuminate\Support\Str::headline($grupo))
                            <div class="border-default-200 rounded-lg border p-4">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="text-default-500 text-xs font-semibold tracking-wide uppercase">
                                        {{ $moduloLabel }}
                                    </p>
                                    <div class="flex items-center gap-1">
                                        <x-shared.button
                                            variant="light"
                                            size="sm"
                                            icon="tabler--checks"
                                            wire:click="marcarModulo('{{ $grupo }}')"
                                        >
                                            Marcar tudo
                                        </x-shared.button>
                                        <x-shared.button
                                            variant="light"
                                            size="sm"
                                            icon="tabler--square-off"
                                            wire:click="limparModulo('{{ $grupo }}')"
                                        >
                                            Limpar
                                        </x-shared.button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach ($permissoes as $permissao)
                                        <x-shared.checkbox
                                            :name="'perm-' . $permissao->id"
                                            :value="$permissao->name"
                                            :label="$permissao->label ?? $permissao->name"
                                            :hint="$permissao->descricao"
                                            wire:model.live="permissions"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <x-shared.empty-state
                                icon="tabler--lock"
                                title="Nenhuma permissão disponível"
                                description="Rode php artisan access:sync para popular o catálogo."
                            />
                        @endforelse
                        @error ('permissions')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    {{-- Leitura: permissões atuais sem edição --}}
                    <div class="space-y-4">
                        @foreach ($permissoesAgrupadas as $grupo => $permissoes)
                            @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($grupo)?->label() ?? \Illuminate\Support\Str::headline($grupo))
                            <div class="border-default-200 rounded-lg border p-4">
                                <p class="text-default-500 mb-3 text-xs font-semibold tracking-wide uppercase">
                                    {{ $moduloLabel }}
                                </p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($permissoes as $permissao)
                                        @php ($ativa = in_array($permissao->name, $permissions, true))
                                        <div class="flex items-center gap-2 text-sm">
                                            <span
                                                @class ([
                                                    'iconify size-4',
                                                    'tabler--circle-check text-success' => $ativa,
                                                    'tabler--circle-x text-default-300' => ! $ativa,
                                                ])
                                            ></span>
                                            <span @class (['text-default-400 line-through' => ! $ativa])>
                                                {{ $permissao->label ?? $permissao->name }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Barra de alterações não salvas --}}
    @if ($podeEditar)
        <x-admin.unsaved-bar :count="$this->alteracoes" save="salvar" discard="descartar" />
    @endif
</div>
