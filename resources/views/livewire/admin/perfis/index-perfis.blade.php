<div class="space-y-6">
    <x-admin.page-header title="Perfis e permissões" subtitle="Defina papéis e o que cada um pode fazer no painel.">
        <x-slot:actions>
            @if ($podeCriar)
                <a class="btn btn-primary" href="{{ route('admin.perfis.create') }}" wire:navigate>
                    <span class="iconify tabler--plus me-1 size-4"></span>
                    Novo perfil
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table w-full" data-testid="tabela-perfis">
                    <thead>
                        <tr>
                            <th>Perfil</th>
                            <th class="text-end">Permissões</th>
                            <th class="text-end">Usuários</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perfis as $perfil)
                            <tr wire:key="perfil-{{ $perfil->id }}">
                                <td>{{ $perfil->name }}</td>
                                <td class="text-end">{{ $perfil->permissions_count }}</td>
                                <td class="text-end">{{ $perfil->users_count }}</td>
                                <td class="text-end">
                                    @can ('update', $perfil)
                                        <a
                                            class="btn btn-sm btn-outline-secondary"
                                            href="{{ route('admin.perfis.edit', $perfil) }}"
                                            wire:navigate
                                        >
                                            Editar
                                        </a>
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
                </table>
            </div>
        </div>
    </div>
</div>
