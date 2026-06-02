<div class="space-y-6">
    <x-admin.page-header title="Usuários admin" subtitle="Gerencie quem tem acesso ao painel administrativo.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.usuarios.create')" icon="tabler--plus" wire:navigate>
                    Novo usuário
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card>
        <x-admin.table.toolbar search-model="busca" search-placeholder="Nome ou e-mail" :density="$densidade">
            <x-slot:filters>
                <x-shared.select
                    name="status"
                    :options="[
                        ['value' => '', 'label' => 'Todos os status'],
                        ['value' => 'ativo', 'label' => 'Ativos'],
                        ['value' => 'inativo', 'label' => 'Inativos'],
                    ]"
                    :placeholder="null"
                    class="w-44"
                    wire:model.live="status"
                />

                <x-shared.select
                    name="role"
                    :options="array_merge([['value' => '', 'label' => 'Todos os perfis']], collect($this->roles)->map(fn ($r) => ['value' => $r, 'label' => $r])->all())"
                    :placeholder="null"
                    class="w-44"
                    wire:model.live="role"
                />

                @if ($busca !== '' || $status !== '' || $role !== '')
                    <x-shared.button appearance="ghost" variant="default" size="sm" wire:click="limparFiltros">
                        Limpar filtros
                    </x-shared.button>
                @endif
            </x-slot:filters>
        </x-admin.table.toolbar>

        <x-admin.table.bulk-bar :count="count($selecionados)" label="usuário(s) selecionado(s)">
            <div class="w-44">
                <x-shared.select
                    name="perfilEmMassa"
                    :options="array_merge([['value' => '', 'label' => 'Atribuir perfil...']], $this->perfisAtribuiveis)"
                    :placeholder="null"
                    wire:model="perfilEmMassa"
                />
            </div>
            <x-shared.button
                variant="primary"
                size="sm"
                wire:click="atribuirPerfilEmMassa"
                wire:confirm="Atribuir o perfil aos usuários selecionados?"
            >
                Aplicar perfil
            </x-shared.button>
            <x-shared.button
                variant="success"
                appearance="soft"
                size="sm"
                wire:click="alternarStatusEmMassa(true)"
                wire:confirm="Reativar os usuários selecionados?"
            >
                Reativar
            </x-shared.button>
            <x-shared.button
                variant="warning"
                appearance="soft"
                size="sm"
                wire:click="alternarStatusEmMassa(false)"
                wire:confirm="Desativar os usuários selecionados?"
            >
                Desativar
            </x-shared.button>
            <x-shared.button variant="default" appearance="ghost" size="sm" wire:click="limparSelecao">
                Limpar
            </x-shared.button>
        </x-admin.table.bulk-bar>

        <x-admin.table.table :density="$densidade" data-testid="tabela-usuarios">
            <thead>
                <tr>
                    <th class="w-10">
                        <input
                            type="checkbox"
                            class="form-checkbox"
                            wire:model.live="selecionarPagina"
                            aria-label="Selecionar página"
                        />
                    </th>
                    <x-admin.table.th-sort column="nome" :sort="$sort" :dir="$dir">Nome</x-admin.table.th-sort>
                    <x-admin.table.th-sort column="email" :sort="$sort" :dir="$dir">E-mail</x-admin.table.th-sort>
                    <th>Perfis</th>
                    <th>Status</th>
                    <x-admin.table.th-sort column="last_login_at" :sort="$sort" :dir="$dir">
                        Último login
                    </x-admin.table.th-sort>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr wire:key="usuario-{{ $usuario->id }}" class="hover:bg-light/40 transition-colors">
                        <td>
                            <input
                                type="checkbox"
                                class="form-checkbox"
                                value="{{ $usuario->id }}"
                                wire:model.live="selecionados"
                                aria-label="Selecionar {{ $usuario->nome }}"
                            />
                        </td>
                        <td class="font-medium">{{ $usuario->nome }}</td>
                        <td class="text-default-600">{{ $usuario->email }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($usuario->roles as $role)
                                    <x-shared.badge>{{ $role->name }}</x-shared.badge>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @if ($usuario->ativo)
                                <x-shared.badge variant="success">Ativo</x-shared.badge>
                            @else
                                <x-shared.badge variant="neutral">Inativo</x-shared.badge>
                            @endif
                        </td>
                        <td class="text-default-600">{{ $usuario->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @can ('update', $usuario)
                                    <x-shared.button
                                        :href="route('admin.usuarios.edit', $usuario)"
                                        variant="default"
                                        appearance="outline"
                                        size="sm"
                                        wire:navigate
                                    >
                                        Editar
                                    </x-shared.button>
                                @endcan

                                @can ('toggleStatus', $usuario)
                                    <x-shared.button
                                        :variant="$usuario->ativo ? 'warning' : 'success'"
                                        appearance="soft"
                                        size="sm"
                                        wire:click="alternarStatus({{ $usuario->id }})"
                                        wire:confirm="{{ $usuario->ativo ? 'Desativar usuário?' : 'Reativar usuário?' }}"
                                    >
                                        {{ $usuario->ativo ? 'Desativar' : 'Reativar' }}
                                    </x-shared.button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-shared.empty-state
                                icon="tabler--users"
                                title="Nenhum usuário admin encontrado"
                                description="Ajuste os filtros ou crie o primeiro usuário do painel."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table.table>

        <div class="mt-4">{{ $usuarios->links() }}</div>
    </x-shared.card>
</div>
