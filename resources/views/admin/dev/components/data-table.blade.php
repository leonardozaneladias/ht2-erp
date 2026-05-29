@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.data-table',
    'description' => 'Tabela única consolidada para listagens administrativas, com busca, exportação, range de datas e seleção em massa.',
])

@php
    $contracts = [
        ['id' => 1, 'codigo' => 'PED-2027', 'equipe' => 'Equipe Comercial', 'empresa' => 'Acme S.A.', 'conclusao' => '20/12/2027', 'aderidos' => 84, 'status' => ['label' => 'Ativo', 'variant' => 'success']],
        ['id' => 2, 'codigo' => 'PED-2026', 'equipe' => 'Equipe de Suporte', 'empresa' => 'Soluções Tech', 'conclusao' => '10/07/2026', 'aderidos' => 46, 'status' => ['label' => 'Em revisão', 'variant' => 'warning']],
        ['id' => 3, 'codigo' => 'PED-2028', 'equipe' => 'Equipe de Operações', 'empresa' => 'Logix Brasil', 'conclusao' => '15/11/2028', 'aderidos' => 118, 'status' => ['label' => 'Planejado', 'variant' => 'info']],
        ['id' => 4, 'codigo' => 'PED-2026B', 'equipe' => 'Equipe Financeira', 'empresa' => 'Northwind', 'conclusao' => '05/08/2026', 'aderidos' => 29, 'status' => ['label' => 'Inativo', 'variant' => 'default']],
    ];

    $installments = [
        ['id' => 1101, 'usuario' => 'Ana Luiza Prado', 'parcela' => '03/12', 'valor' => 'R$ 420,00', 'vencimento' => '08/04/2026', 'status' => ['label' => 'Pendente', 'variant' => 'warning']],
        ['id' => 1102, 'usuario' => 'Bruno Henrique Melo', 'parcela' => '07/10', 'valor' => 'R$ 310,00', 'vencimento' => '15/04/2026', 'status' => ['label' => 'Vencida', 'variant' => 'danger']],
        ['id' => 1103, 'usuario' => 'Carolina Nunes', 'parcela' => '01/08', 'valor' => 'R$ 580,00', 'vencimento' => '22/04/2026', 'status' => ['label' => 'Paga', 'variant' => 'success']],
    ];
@endphp

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Busca, export e filtros" subtitle="Tabela com range de datas e busca por coluna">
            <x-admin.data-table
                :columns="[
                    ['label' => 'Código'],
                    ['label' => 'Equipe'],
                    ['label' => 'Empresa'],
                    ['label' => 'Conclusão', 'type' => 'date'],
                    ['label' => 'Aderidos'],
                    ['label' => 'Status', 'searchable' => false],
                    ['label' => 'Ações', 'orderable' => false, 'searchable' => false],
                ]"
                exportable
                column-search
                date-range
            >
                <x-slot:toolbar>
                    <x-shared.badge variant="info" solid>4 registros</x-shared.badge>
                    <x-shared.button variant="default" appearance="outline" size="sm" icon="tabler--refresh">
                        Atualizar base
                    </x-shared.button>
                </x-slot:toolbar>

                @foreach ($contracts as $contract)
                    <tr>
                        <td>{{ $contract['codigo'] }}</td>
                        <td>{{ $contract['equipe'] }}</td>
                        <td>{{ $contract['empresa'] }}</td>
                        <td>{{ $contract['conclusao'] }}</td>
                        <td>{{ $contract['aderidos'] }}</td>
                        <td>
                            <x-shared.badge :variant="$contract['status']['variant']">
                                {{ $contract['status']['label'] }}
                            </x-shared.badge>
                        </td>
                        <td>
                            <x-shared.dropdown placement="bottom-end">
                                <x-slot:button>
                                    <button
                                        type="button"
                                        class="hs-dropdown-toggle btn btn-icon btn-sm bg-light text-dark"
                                    >
                                        <i class="iconify tabler--dots-vertical text-base"></i>
                                    </button>
                                </x-slot:button>

                                <x-shared.dropdown-item icon="tabler--edit">Editar</x-shared.dropdown-item>
                                <x-shared.dropdown-item icon="tabler--copy">Duplicar</x-shared.dropdown-item>
                                <x-shared.dropdown-divider />
                                <x-shared.dropdown-item icon="tabler--archive" variant="danger">
                                    Arquivar</x-shared.dropdown-item
                                >
                            </x-shared.dropdown>
                        </td>
                    </tr>
                @endforeach
            </x-admin.data-table>
        </x-shared.card>

        <x-shared.card title="Seleção em lote" subtitle="Bulk actions dentro do contrato do próprio data-table">
            <x-admin.data-table
                :columns="[
                    ['label' => 'Usuário'],
                    ['label' => 'Parcela'],
                    ['label' => 'Valor'],
                    ['label' => 'Vencimento', 'type' => 'date'],
                    ['label' => 'Status', 'searchable' => false],
                ]"
                selectable
            >
                <x-slot:bulkActions>
                    <x-shared.button variant="warning" size="sm" icon="tabler--cash">
                        Negociar em lote
                    </x-shared.button>
                    <x-shared.button variant="danger" size="sm" icon="tabler--x"> Cancelar cobrança </x-shared.button>
                </x-slot:bulkActions>

                @foreach ($installments as $installment)
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                value="{{ $installment['id'] }}"
                                class="form-checkbox form-checkbox-light size-4.5"
                                data-af-datatable-row-select
                            />
                        </td>
                        <td>{{ $installment['usuario'] }}</td>
                        <td>{{ $installment['parcela'] }}</td>
                        <td>{{ $installment['valor'] }}</td>
                        <td>{{ $installment['vencimento'] }}</td>
                        <td>
                            <x-shared.badge :variant="$installment['status']['variant']">
                                {{ $installment['status']['label'] }}
                            </x-shared.badge>
                        </td>
                    </tr>
                @endforeach
            </x-admin.data-table>
        </x-shared.card>
    </div>
@endsection
