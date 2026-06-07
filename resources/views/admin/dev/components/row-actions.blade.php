@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.row-actions',
    'description' => 'Padrão ÚNICO de ações por linha: um menu kebab "⋮" que agrupa todas as ações de um registro. Alpine + posição fixed (escapa do overflow da tabela e sobrevive aos morphs do PowerGrid).',
])

@section ('preview')
    <div class="space-y-6">
        <x-shared.card
            title="Ações por linha (kebab)"
            subtitle="Use dentro de PowerGrid via actionsFromView(), ou em tabelas custom"
        >
            <div class="overflow-x-auto">
                <table class="table-hover table-sm table w-full align-middle">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['Maria Souza', 'gestor', 'Ativo', 'success'],
                            ['João Lima', 'super-admin', 'Ativo', 'success'],
                            ['Ana Castro', 'gestor', 'Inativo', 'default'],
                        ] as [$nome, $perfil, $status, $variant])
                            <tr>
                                <td class="font-medium">{{ $nome }}</td>
                                <td><x-shared.badge variant="primary" size="sm">{{ $perfil }}</x-shared.badge></td>
                                <td><x-shared.badge :variant="$variant" size="sm">{{ $status }}</x-shared.badge></td>
                                <td class="text-end">
                                    <x-admin.row-actions>
                                        <x-shared.dropdown-item icon="tabler--edit" :href="'#!'">
                                            Editar
                                        </x-shared.dropdown-item>
                                        <x-shared.dropdown-item icon="tabler--player-pause">
                                            Desativar
                                        </x-shared.dropdown-item>
                                        <x-shared.dropdown-item icon="tabler--login-2">
                                            Entrar como
                                        </x-shared.dropdown-item>
                                        <x-shared.dropdown-divider />
                                        <x-shared.dropdown-item icon="tabler--file-code">
                                            Exportar JSON
                                        </x-shared.dropdown-item>
                                        <x-shared.dropdown-divider />
                                        <x-shared.dropdown-item icon="tabler--user-off" variant="danger">
                                            Anonimizar
                                        </x-shared.dropdown-item>
                                    </x-admin.row-actions>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-shared.card>

        <x-shared.alert variant="info" title="Como usar com PowerGrid">
            No componente PowerGrid, mantenha <code>Column::action('Ações')</code> e implemente
            <code>actionsFromView(mixed $row): ?View</code> retornando uma view <code>_acoes.blade.php</code> que monta
            o <code>&lt;x-admin.row-actions&gt;</code>. Não defina <code>actions()</code> (os botões soltos do
            PowerGrid).
        </x-shared.alert>
    </div>
@endsection
