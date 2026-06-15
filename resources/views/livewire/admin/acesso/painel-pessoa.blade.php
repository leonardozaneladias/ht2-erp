<div class="space-y-4">
    {{-- Cabeçalho (sobre o fundo da seção, como em /admin/configuracoes) --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <x-shared.avatar :name="$nome" :src="$avatarUrl" size="size-11" class="shrink-0" />
            <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold">{{ $nome }}</h3>
                <p class="text-default-500 truncate text-sm">{{ $email }}</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($ativo)
                <span class="badge badge-soft-success">Ativo</span>
            @else
                <span class="badge badge-soft-danger">Inativo</span>
            @endif
            <x-shared.button
                :href="route('admin.usuarios.edit', $usuarioId)"
                variant="default"
                appearance="outline"
                size="sm"
                icon="tabler--user-edit"
                wire:navigate
                title="Editar dados da conta"
            >
                Editar conta
            </x-shared.button>
        </div>
    </div>

    {{-- Abas conectadas (mesmo padrão de /admin/configuracoes) --}}
    <div>
        <x-shared.tab-nav>
            @php ($abas = ['perfis' => 'Perfis', 'acessos' => 'Acessos diretos', 'efetivo' => 'Acesso efetivo'])
            @foreach ($abas as $valor => $rotulo)
                <x-shared.tab-trigger
                    :id="'pessoa-' . $valor"
                    :preline="false"
                    :active="$valor === 'efetivo' ? ! in_array($aba, ['perfis', 'acessos'], true) : $aba === $valor"
                    wire:click="$set('aba', '{{ $valor }}')"
                >
                    {{ $rotulo }}
                    @if ($valor === 'acessos' && $this->acessosDiretos->isNotEmpty())
                        <span class="text-default-400">({{ $this->acessosDiretos->count() }})</span>
                    @endif
                </x-shared.tab-trigger>
            @endforeach
        </x-shared.tab-nav>

        <x-shared.tab-body>
            @if ($aba === 'perfis')
                {{-- ── Aba Perfis (escopo da empresa ativa) ───────────────── --}}
                @php ($superRole = config('access.super_admin_role', 'super-admin'))
                <div class="mb-4 space-y-2">
                    <div class="text-sm">
                        @if ($empresaAtivaNome)
                            <span class="text-default-500">Perfis em</span>
                            <span class="font-semibold">{{ $empresaAtivaNome }}</span>
                        @endif
                    </div>

                    @if (! empty($rolesGlobais))
                        <div
                            class="bg-default-50 border-default-200 flex flex-wrap items-center gap-2 rounded-lg border px-3 py-2"
                        >
                            <span class="text-default-500 text-xs font-medium"
                                >Papéis globais (todas as empresas):</span
                            >
                            @foreach ($rolesGlobais as $papelGlobal)
                                <span
                                    @class ([
                                    'badge',
                                    'badge-soft-warning' => $papelGlobal === $superRole,
                                    'badge-soft-secondary' => $papelGlobal !== $superRole,
                                ])
                                >
                                    @if ($papelGlobal === $superRole)
                                        <span class="iconify tabler--crown me-1 size-4"></span>
                                    @endif
                                    {{ $papelGlobal }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($empresaAtivaId === null)
                    <x-shared.alert variant="warning" icon="tabler--building">
                        Selecione uma empresa no topo para gerir os perfis desta pessoa.
                    </x-shared.alert>
                @elseif ($podeEditarPerfis)
                    @if ($this->rolesGerenciaveis->isEmpty())
                        <x-shared.empty-state
                            icon="tabler--shield-off"
                            title="Nenhum perfil disponível"
                            description="Você só pode atribuir perfis de nível abaixo do seu."
                        />
                    @else
                        <p class="text-default-500 mb-3 text-sm">O acesso da pessoa nesta empresa é a soma de todos os perfis marcados.</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($this->rolesGerenciaveis as $role)
                                <x-shared.checkbox
                                    :name="'role-' . $role->id"
                                    :value="$role->name"
                                    :label="$role->name"
                                    :hint="$role->descricao"
                                    wire:model.live="roles"
                                />
                            @endforeach
                        </div>
                        @error ('roles.*')
                            <p class="form-error mt-2">{{ $message }}</p>
                        @enderror
                    @endif
                @else
                    <div class="space-y-3">
                        <x-shared.alert variant="info" icon="tabler--lock">
                            Você não tem hierarquia para alterar os perfis desta pessoa.
                        </x-shared.alert>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($roles as $nomePerfil)
                                <span class="badge badge-soft-primary">{{ $nomePerfil }}</span>
                            @empty
                                <span class="text-default-500 text-sm">Sem perfis atribuídos nesta empresa.</span>
                            @endforelse
                        </div>
                    </div>
                @endif
            @elseif ($aba === 'acessos')
                {{-- ── Aba Acessos diretos ────────────────────────────────── --}}
                @if ($podeGerirAcessos)
                    @if ($mostrarFormAcesso)
                        <div class="border-default-200 bg-default-50 mb-5 rounded-lg border p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-shared.select-search
                                    name="novoTipo"
                                    label="Tipo"
                                    :options="[
                                        ['value' => 'grant', 'label' => 'Conceder permissão'],
                                        ['value' => 'deny', 'label' => 'Negar permissão'],
                                    ]"
                                    wire:model="novoTipo"
                                />
                                <x-shared.select-search
                                    name="novaPermissao"
                                    label="Permissão"
                                    placeholder="Selecione a permissão"
                                    :options="$this->permissoesParaSelect"
                                    wire:model="novaPermissao"
                                />
                                <x-shared.input
                                    type="datetime-local"
                                    name="novaValidade"
                                    label="Expira em (opcional)"
                                    hint="Deixe vazio para acesso permanente."
                                    wire:model="novaValidade"
                                />
                                <x-shared.input
                                    name="novoMotivo"
                                    label="Motivo"
                                    placeholder="Por que este acesso?"
                                    wire:model="novoMotivo"
                                    required
                                />
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <x-shared.button
                                    variant="default"
                                    appearance="outline"
                                    size="sm"
                                    wire:click="cancelarFormAcesso"
                                >
                                    Cancelar
                                </x-shared.button>
                                <x-shared.button
                                    variant="primary"
                                    size="sm"
                                    wire:click="concederAcesso"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="concederAcesso">Registrar acesso</span>
                                    <span wire:loading wire:target="concederAcesso">Salvando...</span>
                                </x-shared.button>
                            </div>
                        </div>
                    @else
                        <div class="mb-4 flex justify-end">
                            <x-shared.button
                                variant="primary"
                                size="sm"
                                icon="tabler--plus"
                                wire:click="abrirFormAcesso"
                            >
                                Adicionar acesso
                            </x-shared.button>
                        </div>
                    @endif
                @endif
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Permissão</th>
                                <th>Validade</th>
                                <th>Motivo</th>
                                @if ($podeGerirAcessos)
                                    <th class="text-end">Ações</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->acessosDiretos as $grant)
                                <tr wire:key="grant-{{ $grant->id }}">
                                    <td>
                                        @if ($grant->type === \App\Enums\TipoConcessao::Deny)
                                            <x-shared.badge variant="danger" icon="tabler--ban">Nega</x-shared.badge>
                                        @else
                                            <x-shared.badge variant="success" icon="tabler--plus">
                                                Concede</x-shared.badge
                                            >
                                        @endif
                                    </td>
                                    <td>{{ $grant->permission?->label ?? $grant->permission?->name }}</td>
                                    <td>
                                        @if ($grant->expires_at)
                                            <x-shared.badge variant="warning" icon="tabler--clock">
                                                {{ $grant->expires_at->format('d/m/Y H:i') }}
                                            </x-shared.badge>
                                        @else
                                            <x-shared.badge variant="neutral">Permanente</x-shared.badge>
                                        @endif
                                    </td>
                                    <td class="text-default-600 max-w-xs truncate">{{ $grant->reason }}</td>
                                    @if ($podeGerirAcessos)
                                        <td class="text-end">
                                            <x-shared.button
                                                variant="danger"
                                                appearance="outline"
                                                size="sm"
                                                wire:click="solicitarRevogarAcesso({{ $grant->id }})"
                                            >
                                                Revogar
                                            </x-shared.button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $podeGerirAcessos ? 5 : 4 }}">
                                        <x-shared.empty-state
                                            size="sm"
                                            icon="tabler--key-off"
                                            title="Nenhum acesso específico"
                                            description="Esta pessoa usa apenas as permissões dos perfis."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                {{-- ── Aba Acesso efetivo ─────────────────────────────────── --}}
                <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-admin.kpi-card
                        label="Permitidas"
                        :value="(string) $resumo['permitidos']"
                        icon="tabler--circle-check"
                        color="success"
                    />
                    <x-admin.kpi-card
                        label="Negadas/sem acesso"
                        :value="(string) $resumo['negados']"
                        icon="tabler--circle-x"
                        color="danger"
                    />
                    <x-admin.kpi-card
                        label="Total no catálogo"
                        :value="(string) $resumo['total']"
                        icon="tabler--list-check"
                        color="info"
                    />
                </div>
                <x-shared.accordion>
                    @foreach ($acessoEfetivo as $modulo => $itens)
                        @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($modulo)?->label() ?? \Illuminate\Support\Str::headline($modulo))
                        @php ($permitidasNoModulo = $itens->filter(fn ($dto) => $dto->permitido)->count())
                        <x-shared.accordion-item
                            :id="'efe-' . $modulo"
                            :title="$moduloLabel . ' (' . $permitidasNoModulo . '/' . $itens->count() . ')'"
                            :open="$loop->first"
                        >
                            <div class="overflow-x-auto">
                                <table class="table w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Permissão</th>
                                            <th>Situação</th>
                                            <th>Origem</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($itens as $dto)
                                            <tr wire:key="efe-{{ $modulo }}-{{ $dto->ability }}">
                                                <td class="font-medium">{{ $dto->ability }}</td>
                                                <td>
                                                    @if ($dto->permitido)
                                                        <x-shared.badge variant="success" icon="tabler--check">
                                                            Permitido</x-shared.badge
                                                        >
                                                    @elseif ($dto->origem === \App\Enums\OrigemAcesso::Deny)
                                                        <x-shared.badge variant="danger" icon="tabler--ban">
                                                            Negado</x-shared.badge
                                                        >
                                                    @else
                                                        <x-shared.badge variant="neutral" icon="tabler--minus">
                                                            Sem acesso</x-shared.badge
                                                        >
                                                    @endif
                                                </td>
                                                <td>
                                                    <x-shared.badge :variant="$dto->origem->variant()">
                                                        {{ $dto->origem->label() }}
                                                        @if ($dto->roleDeOrigem)
                                                            — {{ $dto->roleDeOrigem }}
                                                        @endif
                                                    </x-shared.badge>
                                                </td>
                                                <td class="text-default-600">
                                                    @if ($dto->expiraEm)
                                                        <span class="inline-flex items-center gap-1">
                                                            <span class="iconify tabler--clock size-4"></span>
                                                            expira {{ $dto->expiraEm->format('d/m/Y H:i') }}
                                                        </span>
                                                    @endif
                                                    @if ($dto->motivo)
                                                        <span
                                                            class="text-default-400 block text-xs"
                                                            >{{ $dto->motivo }}</span
                                                        >
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-shared.accordion-item>
                    @endforeach
                </x-shared.accordion>
            @endif
        </x-shared.tab-body>
    </div>

    {{-- Barra de alterações não salvas (perfis) --}}
    @if ($aba === 'perfis' && $podeEditarPerfis)
        <x-admin.unsaved-bar :count="$this->perfisAlterados" save="salvarPerfis" discard="descartarPerfis" />
    @endif
</div>
