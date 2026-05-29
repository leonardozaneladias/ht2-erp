<div class="space-y-6">
    <x-admin.page-header title="Usuários admin" subtitle="Gerencie quem tem acesso ao painel administrativo.">
        <x-slot:actions>
            @if ($podeCriar)
                <a class="btn btn-primary" href="{{ route('admin.usuarios.create') }}" wire:navigate>
                    <span class="iconify tabler--plus me-1 size-4"></span>
                    Novo usuário
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <x-shared.input
                    name="busca"
                    label="Buscar"
                    placeholder="Nome ou e-mail"
                    wire:model.live.debounce.400ms="busca"
                />

                <x-shared.select
                    name="status"
                    label="Status"
                    :options="[
                        ['value' => '', 'label' => 'Todos'],
                        ['value' => 'ativo', 'label' => 'Ativos'],
                        ['value' => 'inativo', 'label' => 'Inativos'],
                    ]"
                    wire:model.live="status"
                />

                <x-shared.select
                    name="role"
                    label="Perfil"
                    :options="array_merge([['value' => '', 'label' => 'Todos']], collect($this->roles)->map(fn ($r) => ['value' => $r, 'label' => $r])->all())"
                    wire:model.live="role"
                />

                <div class="flex items-end">
                    <button type="button" class="btn btn-outline-secondary" wire:click="limparFiltros">
                        Limpar filtros
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full" data-testid="tabela-usuarios">
                    <thead>
                        <tr>
                            <th>
                                <button type="button" class="link" wire:click="ordenarPor('nome')">Nome</button>
                            </th>
                            <th>
                                <button type="button" class="link" wire:click="ordenarPor('email')">E-mail</button>
                            </th>
                            <th>Perfis</th>
                            <th>Status</th>
                            <th>
                                <button type="button" class="link" wire:click="ordenarPor('last_login_at')">
                                    Último login
                                </button>
                            </th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            <tr wire:key="usuario-{{ $usuario->id }}">
                                <td>{{ $usuario->nome }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td>
                                    @foreach ($usuario->roles as $role)
                                        <x-shared.badge>{{ $role->name }}</x-shared.badge>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($usuario->ativo)
                                        <x-shared.badge variant="success">Ativo</x-shared.badge>
                                    @else
                                        <x-shared.badge variant="neutral">Inativo</x-shared.badge>
                                    @endif
                                </td>
                                <td>{{ $usuario->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    @can ('update', $usuario)
                                        <a
                                            href="{{ route('admin.usuarios.edit', $usuario) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            wire:navigate
                                        >
                                            Editar
                                        </a>
                                    @endcan

                                    @can ('toggleStatus', $usuario)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-{{ $usuario->ativo ? 'warning' : 'success' }}"
                                            wire:click="alternarStatus({{ $usuario->id }})"
                                            wire:confirm="{{ $usuario->ativo ? 'Desativar usuário?' : 'Reativar usuário?' }}"
                                        >
                                            {{ $usuario->ativo ? 'Desativar' : 'Reativar' }}
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-shared.empty-state
                                        icon="tabler--users"
                                        title="Nenhum usuário admin encontrado"
                                        description="Ajuste os filtros ou crie o primeiro usuário do painel."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $usuarios->links() }}</div>
        </div>
    </div>
</div>
