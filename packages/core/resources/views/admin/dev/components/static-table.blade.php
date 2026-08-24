@extends ('admin.dev.components.shell', [
    'title' => 'Preview • Static Table',
    'description' => 'Tabela HTML leve para listas curtas, matrizes simples e conteúdo tabular interno sem DataTables.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Headers via prop" subtitle="Uso ideal para tabelas curtas em cards e subtelas">
            <x-shared.static-table :headers="['Curso', 'Meta', 'Aderidos', 'Status']" striped hover bordered>
                <tr>
                    <td>Administração 2026</td>
                    <td>180</td>
                    <td>132</td>
                    <td><x-shared.badge variant="success">Ativo</x-shared.badge></td>
                </tr>
                <tr>
                    <td>Direito Noturno</td>
                    <td>90</td>
                    <td>54</td>
                    <td><x-shared.badge variant="warning">Atenção</x-shared.badge></td>
                </tr>
                <tr>
                    <td>Engenharia Civil</td>
                    <td>120</td>
                    <td>118</td>
                    <td><x-shared.badge variant="info">Quase completo</x-shared.badge></td>
                </tr>
            </x-shared.static-table>
        </x-shared.card>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-shared.card title="Head e foot customizados" subtitle="Estruturas mais flexíveis com totalizador">
                <x-shared.static-table compact>
                    <x-slot:head>
                        <tr>
                            <th colspan="3">Resumo do Simulador</th>
                        </tr>
                        <tr>
                            <th>Parcela</th>
                            <th>Vencimento</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </x-slot:head>

                    <tr>
                        <td>1/6</td>
                        <td>10/05/2026</td>
                        <td class="text-end">R$ 350,00</td>
                    </tr>
                    <tr>
                        <td>2/6</td>
                        <td>10/06/2026</td>
                        <td class="text-end">R$ 350,00</td>
                    </tr>
                    <tr>
                        <td>3/6</td>
                        <td>10/07/2026</td>
                        <td class="text-end">R$ 350,00</td>
                    </tr>

                    <x-slot:foot>
                        <tr class="font-semibold">
                            <td colspan="2" class="text-end">Total parcial</td>
                            <td class="text-end">R$ 1.050,00</td>
                        </tr>
                    </x-slot:foot>
                </x-shared.static-table>
            </x-shared.card>

            <x-shared.card title="Estado vazio" subtitle="Fallback nativo para tabelas sem linhas">
                <x-shared.static-table
                    :headers="['Módulo', 'Permissão']"
                    empty-message="Nenhuma permissão adicional cadastrada."
                >
                </x-shared.static-table>
            </x-shared.card>
        </div>
    </div>
@endsection
