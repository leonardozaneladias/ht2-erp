<div class="space-y-6">
    <x-admin.page-header title="Perfis e permissões" subtitle="Defina papéis e o que cada um pode fazer no painel.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.perfis.create')" icon="tabler--plus" wire:navigate>
                    Novo perfil
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card>
        <x-admin.table.table :density="'comfortable'" data-testid="tabela-perfis">
            <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Nível</th>
                    <th class="text-end">Permissões</th>
                    <th class="text-end">Usuários</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perfis as $perfil)
                    <tr wire:key="perfil-{{ $perfil->id }}" class="hover:bg-light/40 transition-colors">
                        <td>
                            <div class="font-medium">{{ $perfil->name }}</div>
                            @if ($perfil->descricao)
                                <div class="text-default-500 text-xs">{{ $perfil->descricao }}</div>
                            @endif
                        </td>
                        <td><x-shared.badge variant="neutral">{{ $perfil->nivel }}</x-shared.badge></td>
                        <td class="text-end">{{ $perfil->permissions_count }}</td>
                        <td class="text-end">{{ $perfil->users_count }}</td>
                        <td class="text-end">
                            @can ('update', $perfil)
                                <x-shared.button
                                    :href="route('admin.perfis.edit', $perfil)"
                                    variant="default"
                                    appearance="outline"
                                    size="sm"
                                    wire:navigate
                                >
                                    Editar
                                </x-shared.button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-shared.empty-state
                                icon="tabler--shield-lock"
                                title="Nenhum perfil cadastrado"
                                description="Crie o primeiro perfil para começar a controlar o acesso."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table.table>
    </x-shared.card>
</div>
