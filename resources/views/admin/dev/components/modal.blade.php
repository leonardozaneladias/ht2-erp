@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.modal',
    'description' => 'Modal contextual para formulários rápidos, previews e confirmações ricas, separado do confirm-dialog simples.',
])

@section ('preview')
    <div class="grid gap-6 md:grid-cols-2">
        <x-shared.card title="Ações" subtitle="Três variações comuns no admin">
            <div class="flex flex-wrap gap-3">
                <x-shared.button variant="primary" data-hs-overlay="#modal-programacao-preview">
                    Nova programação
                </x-shared.button>
                <x-shared.button variant="default" appearance="outline" data-hs-overlay="#modal-preview-termo">
                    Preview do termo
                </x-shared.button>
                <x-shared.button variant="danger" data-hs-overlay="#modal-excluir-preview">
                    Excluir registro
                </x-shared.button>
            </div>
        </x-shared.card>

        <x-shared.card title="Quando usar" subtitle="Resumo do contrato oficial">
            <div class="text-default-400 space-y-3 text-sm">
                <p><strong class="text-body-color">Usar modal:</strong> formulários rápidos, previews, contexto rico e ações que não justificam drawer ou página dedicada.</p>
                <p><strong class="text-body-color">Não usar modal:</strong> confirmações simples de sim/não. Para isso, seguir com <code>x-shared.confirm-dialog</code>.</p>
            </div>
        </x-shared.card>
    </div>
    <x-shared.modal id="modal-programacao-preview" title="Nova Programação de Valor" size="lg">
        <div class="grid gap-4 md:grid-cols-2">
            <x-shared.date-picker name="programacao_inicio" label="Data início" value="2026-04-15" />
            <x-shared.date-picker name="programacao_fim" label="Data fim" value="2026-05-30" />
            <x-shared.money-input name="programacao_valor" label="Valor" value="189900" />
            <x-shared.input name="programacao_parcelas" type="number" label="Parcelas máximas" value="12" />
        </div>

        <x-shared.alert variant="warning" class="mt-4">
            Existe uma programação ativa até 14/04/2026. Esta nova faixa vai substituí-la a partir da data inicial.
        </x-shared.alert>

        <x-slot:footer>
            <x-shared.button variant="default" appearance="outline" data-hs-overlay="#modal-programacao-preview">
                Cancelar
            </x-shared.button>
            <x-shared.loading-button variant="primary"> Salvar programação </x-shared.loading-button>
        </x-slot:footer>
    </x-shared.modal>
    <x-shared.modal id="modal-preview-termo" title="Preview do Termo • Registro PED-2027" size="xl">
        <div class="prose prose-sm text-body-color dark:prose-invert max-w-none">
            <h4>Cláusula 1. Objeto</h4>
            <p>O presente termo regula a adesão do usuário ao plano contratado, incluindo cronograma, condições de pagamento e regras de cancelamento.</p>
            <h4>Cláusula 2. Vigência</h4>
            <p>As condições descritas entram em vigor em 15/04/2026 e permanecem válidas até eventual revisão formal de versão.</p>
        </div>

        <x-slot:footer>
            <x-shared.button variant="default" data-hs-overlay="#modal-preview-termo"> Fechar </x-shared.button>
        </x-slot:footer>
    </x-shared.modal>
    <x-shared.modal id="modal-excluir-preview" title="Excluir registro" size="sm" static>
        <p class="text-body-color text-sm">Tem certeza que deseja excluir o registro <strong>PED-2027</strong>?</p>
        <p class="text-default-400 mt-2 text-sm">Essa ação está aqui só para demonstrar um caso de modal contextual. Para prompts simples no sistema real, usar <code>x-shared.confirm-dialog</code>.</p>

        <x-slot:footer>
            <x-shared.button variant="default" appearance="outline" data-hs-overlay="#modal-excluir-preview">
                Cancelar
            </x-shared.button>
            <x-shared.button variant="danger"> Confirmar exclusão </x-shared.button>
        </x-slot:footer>
    </x-shared.modal>
@endsection
