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
            <x-shared.cnpj-input name="cnpj" wire:model="cnpj" />
            <x-shared.input name="inscricao_estadual" label="Inscrição estadual" wire:model="inscricao_estadual" />
            <x-shared.input type="email" name="email" label="E-mail" wire:model="email" />
            <x-shared.phone-input name="telefone" wire:model="telefone" />
            <x-shared.input name="site_url" label="Site" placeholder="https://" wire:model="site_url" />
            <x-shared.toggle
                name="ativo"
                label="Empresa ativa"
                wire:model="ativo"
                stacked
                onText="Ativa"
                offText="Inativa"
            />
        </div>
    </x-shared.card>

    <x-shared.card
        title="Identidade visual"
        subtitle="Cores no formato #RRGGBB. Em branco, herdam o tema da instância."
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-shared.color-picker
                name="cor_primaria"
                label="Cor primária"
                wire:model="cor_primaria"
                :value="$cor_primaria"
                clearable
            />
            <x-shared.color-picker
                name="cor_secundaria"
                label="Cor secundária"
                wire:model="cor_secundaria"
                :value="$cor_secundaria"
                clearable
            />
            <x-shared.color-picker
                name="cor_sucesso"
                label="Cor de sucesso"
                wire:model="cor_sucesso"
                :value="$cor_sucesso"
                clearable
            />
            <x-shared.color-picker
                name="cor_warning"
                label="Cor de alerta"
                wire:model="cor_warning"
                :value="$cor_warning"
                clearable
            />
            <x-shared.color-picker
                name="cor_perigo"
                label="Cor de perigo"
                wire:model="cor_perigo"
                :value="$cor_perigo"
                clearable
            />
            <x-shared.color-picker
                name="cor_info"
                label="Cor de informação"
                wire:model="cor_info"
                :value="$cor_info"
                clearable
            />
        </div>
    </x-shared.card>

    @if ($modo === 'editar')
        <x-shared.card
            title="Filiais"
            subtitle="Unidades da empresa. A Matriz é criada automaticamente e não pode ser desativada."
        >
            <x-slot:headerActions>
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth('admin')->user()?->can('empresas.restaurar'))
                        <x-shared.button
                            :variant="$verFiliaisLixeira ? 'primary' : 'default'"
                            appearance="outline"
                            size="sm"
                            :icon="$verFiliaisLixeira ? 'tabler--arrow-back-up' : 'tabler--trash'"
                            wire:click="alternarFiliaisLixeira"
                        >
                            {{ $verFiliaisLixeira ? 'Ver ativas' : 'Ver lixeira' }}
                        </x-shared.button>
                    @endif

                    @unless ($mostrarFormFilial || $verFiliaisLixeira)
                        <x-shared.button variant="primary" size="sm" icon="tabler--plus" wire:click="abrirFormFilial">
                            Adicionar filial
                        </x-shared.button>
                    @endunless
                </div>
            </x-slot:headerActions>

            @if ($mostrarFormFilial)
                <div class="border-default-200 bg-default-50 mb-5 rounded-lg border p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-shared.input name="filial_nome" label="Nome" wire:model="filial_nome" required />
                        <x-shared.cnpj-input name="filial_cnpj" wire:model="filial_cnpj" />
                        <x-shared.input
                            name="filial_inscricao_estadual"
                            label="Inscrição estadual"
                            wire:model="filial_inscricao_estadual"
                        />
                        <x-shared.phone-input name="filial_telefone" wire:model="filial_telefone" />
                        {{-- Sem extensão de localização instalada, UF e cidade viram
                             texto livre — que é o que a coluna sempre foi no banco
                             (string, sem FK). Ver App\Contracts\Referencia. --}}
                        @if ($this->temCatalogoDeLocalidades)
                            <x-shared.select-search
                                name="filial_estado"
                                label="UF"
                                placeholder="Selecione a UF..."
                                :options="$this->ufsDisponiveis"
                                wire:model.live="filial_estado"
                            />
                            <div wire:key="filial-cidade-{{ $filial_estado }}">
                                <x-shared.select-search
                                    name="filial_cidade"
                                    label="Cidade"
                                    placeholder="Selecione a cidade..."
                                    :options="$this->municipiosDaFilial"
                                    wire:model="filial_cidade"
                                />
                            </div>
                        @else
                            <x-shared.input
                                name="filial_estado"
                                label="UF"
                                maxlength="2"
                                wire:model="filial_estado"
                            />
                            <x-shared.input
                                name="filial_cidade"
                                label="Cidade"
                                wire:model="filial_cidade"
                            />
                        @endif
                        <x-shared.toggle
                            name="filial_ativo"
                            label="Filial ativa"
                            wire:model="filial_ativo"
                            stacked
                            onText="Ativa"
                            offText="Inativa"
                        />
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

            <x-shared.static-table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nome</th>
                        <th scope="col">CNPJ</th>
                        <th scope="col">Cidade/UF</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </x-slot:head>

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
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                @if (! $verFiliaisLixeira)
                                    <x-shared.button
                                        variant="default"
                                        appearance="outline"
                                        size="sm"
                                        icon="tabler--pencil"
                                        wire:click="abrirFormFilial({{ $filial->id }})"
                                    >
                                        Editar
                                    </x-shared.button>
                                    @unless ($filial->e_matriz)
                                        <x-shared.button
                                            variant="danger"
                                            appearance="outline"
                                            size="sm"
                                            icon="tabler--trash"
                                            wire:click="solicitarExcluirFilial({{ $filial->id }})"
                                        >
                                            Excluir
                                        </x-shared.button>
                                    @endunless
                                @else
                                    @if (auth('admin')->user()?->can('empresas.restaurar'))
                                        <x-shared.button
                                            variant="default"
                                            appearance="outline"
                                            size="sm"
                                            icon="tabler--arrow-back-up"
                                            wire:click="solicitarRestaurarFilial({{ $filial->id }})"
                                        >
                                            Restaurar
                                        </x-shared.button>
                                    @endif
                                    @if (auth('admin')->user()?->can('empresas.excluir_permanente'))
                                        <x-shared.button
                                            variant="danger"
                                            appearance="outline"
                                            size="sm"
                                            icon="tabler--trash-x"
                                            wire:click="solicitarExcluirDefinitivoFilial({{ $filial->id }})"
                                        >
                                            Excluir def.
                                        </x-shared.button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-shared.empty-state
                                size="sm"
                                icon="tabler--building-off"
                                :title="$verFiliaisLixeira ? 'Lixeira vazia' : 'Nenhuma filial'"
                                :description="
                                    $verFiliaisLixeira
                                        ? 'Nenhuma filial na lixeira.'
                                        : 'A Matriz é criada junto com a empresa.'
                                "
                            />
                        </td>
                    </tr>
                @endforelse
            </x-shared.static-table>
        </x-shared.card>
    @endif

    <x-admin.form-footer
        :cancel-href="route('admin.empresas.index')"
        :label="$modo === 'criar' ? 'Criar empresa' : 'Salvar alterações'"
    />
</div>
