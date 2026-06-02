@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.table',
    'description' => 'Tabela nativa Livewire: subcomponentes (toolbar, th-sort, bulk-bar, table). Usada em telas mais simples; para grids ricos (filtros, export, bulk) o projeto usa o PowerGrid.',
])

@php
    $rows = [
        ['nome' => 'Ana Luiza Prado', 'email' => 'ana@example.com', 'perfil' => 'gestor', 'status' => ['label' => 'Ativo', 'variant' => 'success']],
        ['nome' => 'Bruno Henrique Melo', 'email' => 'bruno@example.com', 'perfil' => 'rh', 'status' => ['label' => 'Ativo', 'variant' => 'success']],
        ['nome' => 'Carolina Nunes', 'email' => 'carolina@example.com', 'perfil' => 'financeiro', 'status' => ['label' => 'Inativo', 'variant' => 'neutral']],
    ];
@endphp

@section ('preview')
    <div class="grid gap-6">
        <x-shared.alert variant="info" icon="tabler--info-circle">
            Esta é uma demonstração estática do <strong>chrome</strong> da tabela nativa. A versão interativa vive em
            telas Livewire como
            <a class="text-primary font-medium underline" href="{{ route('admin.perfis.index') }}">Perfis</a>
            e
            <a class="text-primary font-medium underline" href="{{ route('admin.auditoria.index') }}"
                >Logs de auditoria</a
            >. Para grids ricos (filtros avançados, export, ações em massa), veja
            <a class="text-primary font-medium underline" href="{{ route('admin.usuarios.index') }}">Usuários admin</a>
            (PowerGrid).
        </x-shared.alert>

        <x-shared.card title="Tabela completa" subtitle="toolbar + th-sort + bulk-bar + table">
            <x-admin.table.toolbar :density="'comfortable'" search-placeholder="Buscar registros...">
                <x-slot:filters>
                    <x-shared.select
                        name="demo-status"
                        :options="[['value' => '', 'label' => 'Todos os status'], ['value' => 'ativo', 'label' => 'Ativos']]"
                        :placeholder="null"
                        class="w-44"
                    />
                </x-slot:filters>
            </x-admin.table.toolbar>

            <x-admin.table.bulk-bar :count="2" label="registro(s) selecionado(s)">
                <x-shared.button variant="primary" size="sm">Aplicar perfil</x-shared.button>
                <x-shared.button variant="success" appearance="soft" size="sm">Reativar</x-shared.button>
                <x-shared.button variant="default" appearance="ghost" size="sm">Limpar</x-shared.button>
            </x-admin.table.bulk-bar>

            <x-admin.table.table>
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" class="form-checkbox" aria-label="Selecionar página" />
                        </th>
                        <x-admin.table.th-sort column="nome" sort="nome" dir="asc">Nome</x-admin.table.th-sort>
                        <x-admin.table.th-sort column="email" sort="nome" dir="asc">E-mail</x-admin.table.th-sort>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="hover:bg-light/40 transition-colors">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-checkbox"
                                    aria-label="Selecionar {{ $row['nome'] }}"
                                />
                            </td>
                            <td class="font-medium">{{ $row['nome'] }}</td>
                            <td class="text-default-600">{{ $row['email'] }}</td>
                            <td><x-shared.badge>{{ $row['perfil'] }}</x-shared.badge></td>
                            <td>
                                <x-shared.badge :variant="$row['status']['variant']">
                                    {{ $row['status']['label'] }}</x-shared.badge
                                >
                            </td>
                            <td class="text-end">
                                <div class="inline-flex justify-end gap-1.5">
                                    <x-shared.button variant="default" appearance="outline" size="sm">
                                        Editar</x-shared.button
                                    >
                                    <x-shared.button variant="warning" appearance="soft" size="sm">
                                        Desativar</x-shared.button
                                    >
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table.table>

            <div class="text-default-400 mt-4 text-sm">Paginação via <code>$items->links()</code> nas telas reais.</div>
        </x-shared.card>

        <x-shared.card title="Densidade compacta" subtitle="table-sm via prop :density='compact'">
            <x-admin.table.table :density="'compact'">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="font-medium">{{ $row['nome'] }}</td>
                            <td class="text-default-600">{{ $row['email'] }}</td>
                            <td><x-shared.badge>{{ $row['perfil'] }}</x-shared.badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table.table>
        </x-shared.card>
    </div>
@endsection
