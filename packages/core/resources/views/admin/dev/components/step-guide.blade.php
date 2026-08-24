@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.step-guide',
    'description' => 'Timeline vertical de instruções com ações contextuais embutidas por passo — coluna "Como funciona" das telas de importação e fluxos guiados.',
])

@section ('preview')
    <div class="grid items-start gap-6 lg:grid-cols-2">
        <x-shared.card title="Como funciona" subtitle="Passos com ícone e ação contextual embutida (slot action)">
            <x-shared.step-guide>
                <x-shared.step-guide-item icon="tabler--file-spreadsheet" title="Baixe o modelo">
                    Planilha com os cabeçalhos corretos, exemplos e instruções.
                    <x-slot:action>
                        <x-shared.button
                            type="button"
                            size="sm"
                            variant="primary"
                            appearance="outline"
                            icon="tabler--download"
                        >
                            Baixar planilha modelo
                        </x-shared.button>
                    </x-slot:action>
                </x-shared.step-guide-item>

                <x-shared.step-guide-item icon="tabler--pencil" title="Preencha os dados">
                    Só a primeira aba é obrigatória — as demais complementam o cadastro.
                </x-shared.step-guide-item>

                <x-shared.step-guide-item icon="tabler--search" title="Envie e confira a análise">
                    Prévia do que seria criado ou atualizado — nada é gravado ainda.
                </x-shared.step-guide-item>

                <x-shared.step-guide-item icon="tabler--checklist" title="Confirme a importação" last>
                    Reimportar atualiza em vez de duplicar registros.
                </x-shared.step-guide-item>
            </x-shared.step-guide>
        </x-shared.card>

        <x-shared.card title="Numerado" subtitle="Sem ícone, o nó mostra o número informado em :index">
            <x-shared.step-guide>
                <x-shared.step-guide-item :index="1" title="Exporte o arquivo do equipamento">
                    No software do relógio de ponto, exporte o AFD do período.
                </x-shared.step-guide-item>

                <x-shared.step-guide-item :index="2" title="Envie o arquivo">
                    Arraste o .txt na zona de upload ao lado.
                </x-shared.step-guide-item>

                <x-shared.step-guide-item :index="3" title="Confira o relatório" last>
                    As marcações importadas aparecem no espelho de ponto.
                </x-shared.step-guide-item>
            </x-shared.step-guide>
        </x-shared.card>
    </div>
@endsection
