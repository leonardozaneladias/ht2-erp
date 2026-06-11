<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo usuário admin' : 'Editar usuário admin'"
        subtitle="Defina identificação, perfis de acesso e concessões específicas."
    >
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.usuarios.index') }}" wire:navigate>Cancelar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.tab-nav>
        <x-shared.tab-trigger id="aba-dados" active icon="tabler--user">Dados</x-shared.tab-trigger>
        <x-shared.tab-trigger id="aba-perfis" icon="tabler--shield-lock">Perfis</x-shared.tab-trigger>
        @if ($modo === 'editar')
            <x-shared.tab-trigger id="aba-empresas" icon="tabler--building-community">Empresas</x-shared.tab-trigger>
            <x-shared.tab-trigger id="aba-acessos" icon="tabler--key">Acessos extras</x-shared.tab-trigger>
        @endif
    </x-shared.tab-nav>

    <div class="mt-5 space-y-6">
        {{-- ABA DADOS --}}
        <x-shared.tab-panel id="aba-dados" active>
            <x-shared.card title="Identificação">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-shared.input name="nome" label="Nome completo" wire:model="nome" required />
                    <x-shared.input type="email" name="email" label="E-mail" wire:model="email" required />

                    @if ($modo === 'criar')
                        <div class="md:col-span-2">
                            <x-shared.radio-group name="modoAcesso" label="Como o usuário recebe o acesso?" inline>
                                <x-shared.radio
                                    name="modoAcesso"
                                    value="convite"
                                    label="Enviar convite por e-mail"
                                    description="O usuário define a própria senha pelo link (válido por 7 dias)."
                                    wire:model.live="modoAcesso"
                                />
                                <x-shared.radio
                                    name="modoAcesso"
                                    value="manual"
                                    label="Definir senha manualmente"
                                    description="Você informa a senha e a repassa ao usuário."
                                    wire:model.live="modoAcesso"
                                />
                            </x-shared.radio-group>
                        </div>
                    @endif

                    @if ($modo === 'editar' || $modoAcesso === 'manual')
                        <x-shared.password-input
                            name="password"
                            :label="$modo === 'criar' ? 'Senha' : 'Nova senha (deixe vazio para manter)'"
                            wire:model="password"
                            :required="$modo === 'criar'"
                        />
                    @endif

                    <x-shared.toggle name="ativo" label="Usuário ativo" wire:model="ativo" />

                    @if ($modo === 'editar' && $this->emailNaoVerificado)
                        <div class="md:col-span-2">
                            <x-shared.alert variant="info" icon="tabler--mail-question">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <span>Este usuário ainda não ativou a conta pelo convite.</span>
                                    <x-shared.button
                                        variant="primary"
                                        size="sm"
                                        icon="tabler--send"
                                        wire:click="reenviarConvite"
                                        wire:loading.attr="disabled"
                                    >
                                        Reenviar convite
                                    </x-shared.button>
                                </div>
                            </x-shared.alert>
                        </div>
                    @endif
                </div>
            </x-shared.card>
        </x-shared.tab-panel>

        {{-- ABA PERFIS --}}
        <x-shared.tab-panel id="aba-perfis">
            <x-shared.card title="Perfis do usuário" subtitle="O acesso é a soma de todos os perfis marcados.">
                @if ($rolesDisponiveis->isEmpty())
                    <x-shared.empty-state
                        icon="tabler--shield-off"
                        title="Nenhum perfil disponível"
                        description="Você só pode atribuir perfis de nível abaixo do seu."
                    />
                @else
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @foreach ($rolesDisponiveis as $role)
                            <x-shared.checkbox
                                :name="'roles_' . $role->name"
                                :value="$role->name"
                                :label="$role->name"
                                :hint="$role->descricao"
                                wire:model="roles"
                            />
                        @endforeach
                    </div>
                @endif
                @error ('roles')
                    <p class="form-error mt-2">{{ $message }}</p>
                @enderror
                @error ('roles.*')
                    <p class="form-error mt-2">{{ $message }}</p>
                @enderror
            </x-shared.card>
        </x-shared.tab-panel>

        {{-- ABA EMPRESAS --}}
        @if ($modo === 'editar')
            <x-shared.tab-panel id="aba-empresas">
                <x-shared.card
                    title="Empresas acessíveis"
                    subtitle="Empresas em que o usuário pode operar (todas as filiais)."
                >
                    @if (! $this->podeGerirEmpresas)
                        <x-shared.alert variant="info" icon="tabler--lock">
                            Você não tem permissão para gerenciar o acesso a empresas.
                        </x-shared.alert>
                    @elseif ($this->empresasDisponiveis->isEmpty())
                        <x-shared.empty-state
                            icon="tabler--building-off"
                            title="Nenhuma empresa cadastrada"
                            description="Cadastre empresas para conceder acesso."
                        />
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach ($this->empresasDisponiveis as $empresa)
                                <x-shared.checkbox
                                    :name="'empresa_' . $empresa->id"
                                    :value="$empresa->id"
                                    :label="$empresa->nome"
                                    wire:model="empresasAcesso"
                                />
                            @endforeach
                        </div>
                        <div class="mt-4 flex justify-end">
                            <x-shared.button
                                variant="primary"
                                size="sm"
                                wire:click="salvarEmpresas"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="salvarEmpresas">Salvar acesso</span>
                                <span wire:loading wire:target="salvarEmpresas">Salvando...</span>
                            </x-shared.button>
                        </div>
                    @endif
                </x-shared.card>
            </x-shared.tab-panel>
        @endif

        {{-- ABA ACESSOS EXTRAS --}}
        @if ($modo === 'editar')
            <x-shared.tab-panel id="aba-acessos">
                <x-shared.card
                    title="Acessos específicos"
                    subtitle="Conceda ou negue permissões pontuais, com validade e motivo. Negações têm prioridade sobre os perfis."
                >
                    <x-slot:headerActions>
                        @unless ($mostrarFormAcesso)
                            <x-shared.button
                                variant="primary"
                                size="sm"
                                icon="tabler--plus"
                                wire:click="abrirFormAcesso"
                            >
                                Adicionar acesso
                            </x-shared.button>
                        @endunless
                    </x-slot:headerActions>

                    @if ($mostrarFormAcesso)
                        <div class="border-default-200 bg-default-50 mb-5 rounded-lg border p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-shared.select
                                    name="novoTipo"
                                    label="Tipo"
                                    :options="[
                                        ['value' => 'grant', 'label' => 'Conceder permissão'],
                                        ['value' => 'deny', 'label' => 'Negar permissão'],
                                    ]"
                                    wire:model="novoTipo"
                                />
                                <x-shared.select
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
                                    wire:click="concederAcessoExtra"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="concederAcessoExtra">Registrar acesso</span>
                                    <span wire:loading wire:target="concederAcessoExtra">Salvando...</span>
                                </x-shared.button>
                            </div>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Permissão</th>
                                    <th>Validade</th>
                                    <th>Motivo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->acessosExtras as $grant)
                                    <tr wire:key="grant-{{ $grant->id }}">
                                        <td>
                                            @if ($grant->type === \App\Enums\TipoConcessao::Deny)
                                                <x-shared.badge variant="danger" icon="tabler--ban">
                                                    Nega</x-shared.badge
                                                >
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
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="revogarAcessoExtra({{ $grant->id }})"
                                                wire:confirm="Revogar este acesso?"
                                            >
                                                Revogar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <x-shared.empty-state
                                                size="sm"
                                                icon="tabler--key-off"
                                                title="Nenhum acesso específico"
                                                description="Este usuário usa apenas as permissões dos perfis."
                                            />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-shared.card>
            </x-shared.tab-panel>
        @endif

        {{-- RODAPÉ: salvar dados/perfis --}}
        <div class="flex justify-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.usuarios.index') }}" wire:navigate>Cancelar</a>
            <button type="button" class="btn btn-primary" wire:click="salvar" wire:loading.attr="disabled">
                <span
                    wire:loading.remove
                    wire:target="salvar"
                    >{{ $modo === 'criar' ? 'Criar usuário' : 'Salvar alterações' }}</span
                >
                <span wire:loading wire:target="salvar">Salvando...</span>
            </button>
        </div>
    </div>
</div>
