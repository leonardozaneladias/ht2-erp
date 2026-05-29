@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.textarea',
    'description' => 'Versão multi-linha da base de input, com suporte a hint, rows customizado e conteúdo inicial.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Textarea base" subtitle="Rows diferentes e conteúdo inicial">
            <div class="space-y-4">
                <x-shared.textarea
                    name="observacoes"
                    label="Observações"
                    hint="Use este campo para registrar combinações importantes do registro."
                />

                <x-shared.textarea name="descricao_produto" label="Descrição do produto" rows="6">
                    Plano com cobertura completa, suporte premium e entrega escalonada por equipe.
                </x-shared.textarea>
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Configurações globais">
            <div class="space-y-4">
                <x-shared.textarea
                    name="mensagem_rodape"
                    label="Rodapé padrão dos documentos"
                    rows="5"
                    hint="Texto exibido em propostas e contratos emitidos pelo backoffice."
                    value="Este documento foi emitido automaticamente pelo Sistema Admin."
                />

                <x-shared.textarea name="mensagem_boas_vindas" label="Mensagem de boas-vindas do portal" rows="4">
                    Bem-vindo ao portal do cliente. Aqui você acompanha pagamentos, documentos e novidades da sua
                    equipe.</x-shared.textarea
                >
            </div>
        </x-shared.card>
    </div>
@endsection
