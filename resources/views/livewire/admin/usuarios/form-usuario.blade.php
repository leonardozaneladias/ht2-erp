<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo usuário admin' : 'Editar usuário admin'"
        subtitle="Defina identificação, perfis de acesso e concessões específicas."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Usuários', 'url' => route('admin.usuarios.index')],
            ['label' => $modo === 'criar' ? 'Novo usuário' : $nome, 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button :href="route('admin.usuarios.index')" variant="default" appearance="outline" wire:navigate>
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.tab-nav>
        <x-shared.tab-trigger
            id="aba-dados"
            active
            icon="tabler--user"
            :has-error="$errors->hasAny(['nome', 'email', 'password', 'modoAcesso', 'ativo'])"
        >
            Dados
        </x-shared.tab-trigger>
        <x-shared.tab-trigger
            id="aba-perfis"
            icon="tabler--shield-lock"
            :has-error="$errors->hasAny(['roles', 'roles.*'])"
        >
            Perfis
        </x-shared.tab-trigger>
        @if ($modo === 'editar')
            <x-shared.tab-trigger id="aba-empresas" icon="tabler--building-community">Empresas</x-shared.tab-trigger>
            <x-shared.tab-trigger id="aba-acessos" icon="tabler--key">Acessos extras</x-shared.tab-trigger>
            @if ($this->podeGerirDoisFatores)
                <x-shared.tab-trigger id="aba-seguranca" icon="tabler--lock">Segurança</x-shared.tab-trigger>
            @endif
            @if ($this->podeVerHistorico)
                <x-shared.tab-trigger id="aba-historico" icon="tabler--history">Histórico</x-shared.tab-trigger>
            @endif
        @endif
    </x-shared.tab-nav>

    <x-shared.tab-body>
        {{-- ABA DADOS --}}
        <x-shared.tab-panel id="aba-dados" active>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-shared.avatar-cropper
                        model="avatar"
                        :name="$nome !== '' ? $nome : 'Novo usuário'"
                        :current="$this->avatarAtualUrl"
                        :pending="$this->avatarPendenteUrl()"
                        :remove-action="$modo === 'editar' ? 'removerFoto' : null"
                    />
                </div>

                <x-shared.input name="nome" label="Nome completo" wire:model="nome" required />
                <x-shared.input type="email" name="email" label="E-mail" wire:model="email" required />
                <x-shared.phone-input name="telefone" label="Telefone" wire:model="telefone" />
                <x-shared.select-search
                    name="cargo"
                    label="Cargo"
                    placeholder="Selecione o cargo..."
                    :options="$this->cargosDisponiveis"
                    wire:model="cargo"
                />

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
                        with-meter
                    />
                @endif

                <x-shared.toggle
                    name="ativo"
                    label="Usuário ativo"
                    wire:model="ativo"
                    stacked
                    onText="Ativo"
                    offText="Inativo"
                />

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
        </x-shared.tab-panel>

        {{-- ABA PERFIS --}}
        <x-shared.tab-panel id="aba-perfis">
            <p class="text-default-500 mb-5 text-sm">O acesso é a soma de todos os perfis marcados.</p>

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
        </x-shared.tab-panel>

        {{-- ABA EMPRESAS --}}
        @if ($modo === 'editar')
            <x-shared.tab-panel id="aba-empresas">
                <p class="text-default-500 mb-5 text-sm">Empresas em que o usuário pode operar (todas as filiais).</p>

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
            </x-shared.tab-panel>
        @endif

        {{-- ABA ACESSOS EXTRAS --}}
        @if ($modo === 'editar')
            <x-shared.tab-panel id="aba-acessos">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <p class="text-default-500 text-sm">Conceda ou negue permissões pontuais, com validade e motivo. Negações têm prioridade sobre os perfis.</p>
                    @unless ($mostrarFormAcesso)
                        <x-shared.button variant="primary" size="sm" icon="tabler--plus" wire:click="abrirFormAcesso">
                            Adicionar acesso
                        </x-shared.button>
                    @endunless
                </div>

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
                                    <td class="text-end">
                                        <x-shared.button
                                            variant="danger"
                                            appearance="outline"
                                            size="sm"
                                            wire:click="solicitarRevogarAcessoExtra({{ $grant->id }})"
                                        >
                                            Revogar
                                        </x-shared.button>
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
            </x-shared.tab-panel>
        @endif

        {{-- ABA SEGURANÇA --}}
        @if ($modo === 'editar' && $this->podeGerirDoisFatores)
            <x-shared.tab-panel id="aba-seguranca">
                <p class="text-default-500 mb-5 text-sm">Controle o segundo fator por e-mail deste usuário.</p>

                @if (! $this->emailDoisFatoresGlobalAtivo)
                    <x-shared.alert variant="info" icon="tabler--lock">
                        O 2FA por e-mail está desligado nas Configurações do sistema. Habilite-o em Configurações ›
                        Segurança para poder usá-lo aqui.
                    </x-shared.alert>
                @else
                    <x-shared.toggle
                        name="emailDoisFatoresAlvo"
                        label="Permitir código 2FA por e-mail"
                        wire:model="emailDoisFatoresAlvo"
                    />
                    <p class="text-default-500 mt-1 text-sm">O usuário poderá receber um código de verificação por e-mail ao entrar, mesmo sem o aplicativo autenticador.</p>
                    <div class="mt-4 flex justify-end">
                        <x-shared.button
                            variant="primary"
                            size="sm"
                            wire:click="salvarDoisFatoresEmail"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="salvarDoisFatoresEmail">Salvar</span>
                            <span wire:loading wire:target="salvarDoisFatoresEmail">Salvando...</span>
                        </x-shared.button>
                    </div>
                @endif
            </x-shared.tab-panel>
        @endif

        {{-- ABA HISTÓRICO: trilha de auditoria do registro (sem moldura própria; o tab-body é a superfície) --}}
        @if ($modo === 'editar' && $this->podeVerHistorico)
            <x-shared.tab-panel id="aba-historico">
                <livewire:admin.auditoria.historico-registro
                    :subject-type="\App\Models\AdminUser::class"
                    :subject-id="$usuarioId"
                    bare
                    :key="'historico-usuario-' . $usuarioId"
                />
            </x-shared.tab-panel>
        @endif
    </x-shared.tab-body>

    {{-- RODAPÉ: salvar dados/perfis --}}
    <x-admin.form-footer
        :cancel-href="route('admin.usuarios.index')"
        :label="$modo === 'criar' ? 'Criar usuário' : 'Salvar alterações'"
    />
</div>
