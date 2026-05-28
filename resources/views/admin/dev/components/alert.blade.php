@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.alert',
    'description' => 'Estados persistentes para dashboard, validação cruzada, avisos de configuração e flashes do sistema.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Variações principais" subtitle="Soft, solid, com título e dismissible">
            <div class="space-y-4">
                <x-shared.alert variant="success">
                    Contrato criado com sucesso e pronto para receber programações.
                </x-shared.alert>

                <x-shared.alert variant="warning" title="Configuração pendente">
                    Esta turma ainda não possui programação ativa para boleto e PIX.
                </x-shared.alert>

                <x-shared.alert variant="danger" dismissible>
                    Não foi possível aplicar o reajuste porque existem parcelas em processamento.
                </x-shared.alert>

                <x-shared.alert variant="info" solid icon="tabler--sparkles">
                    O dashboard foi recalculado agora há pouco com os dados mais recentes do financeiro.
                </x-shared.alert>
            </div>
        </x-shared.card>

        <x-shared.card title="Exemplo de domínio" subtitle="Alertas do dashboard administrativo">
            <div class="space-y-4">
                <x-shared.alert variant="warning" title="Contratos sem programação ativa">
                    Existem <strong>4 contratos</strong> sem programação vigente.
                    <a href="#!">Revisar agora</a>
                </x-shared.alert>

                <x-shared.alert variant="danger" title="Parcelas vencidas aguardando ação">
                    <strong>18 parcelas</strong> continuam sem tentativa de renegociação.
                    <a href="#!">Abrir listagem</a>
                </x-shared.alert>
            </div>
        </x-shared.card>
    </div>
@endsection
