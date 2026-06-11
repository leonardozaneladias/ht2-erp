<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Nova empresa' : 'Editar empresa'"
        subtitle="Dados cadastrais e identidade visual aplicada quando a empresa está ativa."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Empresas', 'url' => route('admin.empresas.index')],
            ['label' => $modo === 'criar' ? 'Nova empresa' : $nome, 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button :href="route('admin.empresas.index')" variant="default" appearance="outline" wire:navigate>
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card title="Identificação">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="nome" label="Nome" wire:model="nome" required />
            <x-shared.input name="razao_social" label="Razão social" wire:model="razao_social" />
            <x-shared.input name="cnpj" label="CNPJ" placeholder="00.000.000/0000-00" wire:model="cnpj" />
            <x-shared.input name="inscricao_estadual" label="Inscrição estadual" wire:model="inscricao_estadual" />
            <x-shared.input type="email" name="email" label="E-mail" wire:model="email" />
            <x-shared.input name="telefone" label="Telefone" wire:model="telefone" />
            <x-shared.input name="site_url" label="Site" placeholder="https://" wire:model="site_url" />
            <x-shared.toggle name="ativo" label="Empresa ativa" wire:model="ativo" />
        </div>
    </x-shared.card>

    <x-shared.card
        title="Identidade visual"
        subtitle="Cores no formato #RRGGBB. Em branco, herdam o tema da instância."
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-shared.input name="cor_primaria" label="Cor primária" placeholder="#RRGGBB" wire:model="cor_primaria" />
            <x-shared.input
                name="cor_secundaria"
                label="Cor secundária"
                placeholder="#RRGGBB"
                wire:model="cor_secundaria"
            />
            <x-shared.input name="cor_sucesso" label="Cor de sucesso" placeholder="#RRGGBB" wire:model="cor_sucesso" />
            <x-shared.input name="cor_warning" label="Cor de alerta" placeholder="#RRGGBB" wire:model="cor_warning" />
            <x-shared.input name="cor_perigo" label="Cor de perigo" placeholder="#RRGGBB" wire:model="cor_perigo" />
            <x-shared.input name="cor_info" label="Cor de informação" placeholder="#RRGGBB" wire:model="cor_info" />
        </div>
    </x-shared.card>

    @if ($modo === 'editar')
        <x-shared.card
            title="Filiais"
            subtitle="Unidades da empresa. A Matriz é criada automaticamente e não pode ser desativada."
        >
            <x-slot:headerActions>
                @unless ($mostrarFormFilial)
                    <x-shared.button variant="primary" size="sm" icon="tabler--plus" wire:click="abrirFormFilial">
                        Adicionar filial
                    </x-shared.button>
                @endunless
            </x-slot:headerActions>

            @if ($mostrarFormFilial)
                <div class="border-default-200 bg-default-50 mb-5 rounded-lg border p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-shared.input name="filial_nome" label="Nome" wire:model="filial_nome" required />
                        <x-shared.input
                            name="filial_cnpj"
                            label="CNPJ"
                            placeholder="00.000.000/0000-00"
                            wire:model="filial_cnpj"
                        />
                        <x-shared.input
                            name="filial_inscricao_estadual"
                            label="Inscrição estadual"
                            wire:model="filial_inscricao_estadual"
                        />
                        <x-shared.input name="filial_telefone" label="Telefone" wire:model="filial_telefone" />
                        <x-shared.input name="filial_cidade" label="Cidade" wire:model="filial_cidade" />
                        <x-shared.input
                            name="filial_estado"
                            label="UF"
                            placeholder="SP"
                            maxlength="2"
                            wire:model="filial_estado"
                        />
                        <x-shared.toggle name="filial_ativo" label="Filial ativa" wire:model="filial_ativo" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-shared.button
                            variant="default"
                            appearance="outline"
                            size="sm"
                            wire:click="cancelarFormFilial"
                        >
                            Cancelar
                        </x-shared.button>
                        <x-shared.button
                            variant="primary"
                            size="sm"
                            wire:click="salvarFilial"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="salvarFilial">
                                {{ $filialEditandoId === null ? 'Criar filial' : 'Salvar filial' }}
                            </span>
                            <span wire:loading wire:target="salvarFilial">Salvando...</span>
                        </x-shared.button>
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Cidade/UF</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->filiais as $filial)
                            <tr wire:key="filial-{{ $filial->id }}">
                                <td>
                                    <span class="font-medium">{{ $filial->nome }}</span>
                                    @if ($filial->e_matriz)
                                        <x-shared.badge variant="primary" class="ms-2">Matriz</x-shared.badge>
                                    @endif
                                </td>
                                <td>{{ $filial->cnpj ?: '—' }}</td>
                                <td>
                                    {{ $filial->cidade ? $filial->cidade . ($filial->estado ? '/' . $filial->estado : '') : '—' }}
                                </td>
                                <td>
                                    @if ($filial->ativo)
                                        <x-shared.badge variant="success">Ativa</x-shared.badge>
                                    @else
                                        <x-shared.badge variant="neutral">Inativa</x-shared.badge>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-shared.button
                                        variant="default"
                                        appearance="outline"
                                        size="sm"
                                        icon="tabler--pencil"
                                        wire:click="abrirFormFilial({{ $filial->id }})"
                                    >
                                        Editar
                                    </x-shared.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-shared.empty-state
                                        size="sm"
                                        icon="tabler--building-off"
                                        title="Nenhuma filial"
                                        description="A Matriz é criada junto com a empresa."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-shared.card>
    @endif

    <x-admin.form-footer
        :cancel-href="route('admin.empresas.index')"
        :label="$modo === 'criar' ? 'Criar empresa' : 'Salvar alterações'"
    />
</div>
