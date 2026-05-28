@extends ('admin.dev.components.shell', [
    'title' => 'Preview • helper x-shared.confirm-dialog',
    'description' => 'Bridge SweetAlert2 para confirmações simples, destrutivas e alertas críticos.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Confirmações" subtitle="Helpers globais do batch">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                    <p class="text-body-color mb-3 text-sm font-medium">Ação destrutiva</p>

                    <x-shared.button variant="danger" icon="tabler--trash" data-confirm-demo="destructive">
                        Excluir contrato
                    </x-shared.button>
                </div>

                <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                    <p class="text-body-color mb-3 text-sm font-medium">Ação informativa</p>

                    <x-shared.button variant="info" icon="tabler--shield-question" data-confirm-demo="informative">
                        Confirmar publicação
                    </x-shared.button>
                </div>

                <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                    <p class="text-body-color mb-3 text-sm font-medium">Alert de sucesso</p>

                    <x-shared.button variant="success" icon="tabler--circle-check" data-alert-demo="success">
                        Mostrar sucesso
                    </x-shared.button>
                </div>

                <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                    <p class="text-body-color mb-3 text-sm font-medium">Alert de erro</p>

                    <x-shared.button variant="warning" icon="tabler--alert-triangle" data-alert-demo="error">
                        Mostrar erro
                    </x-shared.button>
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Reajuste financeiro">
            <div class="border-default-300 bg-card rounded-2xl border p-4">
                <div class="space-y-2">
                    <p class="text-body-color font-semibold">Aplicar reajuste nas parcelas em aberto</p>
                    <p class="text-default-400 text-sm">Use o helper quando a decisão for simples e binária. Se a confirmação precisar de formulário, histórico ou preview, a decisão oficial é migrar para <code>x-shared.modal</code>.</p>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <x-shared.button variant="primary" icon="tabler--sparkles" data-confirm-demo="reajuste">
                        Confirmar reajuste
                    </x-shared.button>
                    <x-shared.button
                        variant="default"
                        appearance="outline"
                        icon="tabler--article"
                        data-confirm-demo="termo"
                    >
                        Arquivar versão do termo
                    </x-shared.button>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection

@section ('previewScripts')
    <script>
        document.querySelectorAll('[data-confirm-demo]').forEach((button) => {
            button.addEventListener('click', async () => {
                const type = button.getAttribute('data-confirm-demo');

                if (type === 'destructive') {
                    const result = await window.confirmDestructive({
                        title: 'Excluir contrato?',
                        text: 'Esta ação removerá o contrato e desvinculará os formandos associados.',
                        confirmText: 'Sim, excluir',
                    });

                    if (result.isConfirmed) {
                        window.showToast({
                            variant: 'success',
                            title: 'Contrato excluído',
                            message: 'A ação foi confirmada com sucesso na preview.',
                        });
                    }

                    return;
                }

                if (type === 'reajuste') {
                    const result = await window.confirmDestructive({
                        title: 'Aplicar reajuste?',
                        text: 'As parcelas em aberto serão recalculadas com o novo índice.',
                        confirmText: 'Sim, aplicar',
                    });

                    if (result.isConfirmed) {
                        window.showToast({
                            variant: 'warning',
                            title: 'Reajuste em fila',
                            message: 'O recálculo foi agendado para processamento assíncrono.',
                        });
                    }

                    return;
                }

                if (type === 'termo') {
                    const result = await window.confirmInfo({
                        title: 'Arquivar versão atual?',
                        text: 'O histórico do termo será preservado e uma nova revisão será aberta.',
                        confirmText: 'Arquivar',
                    });

                    if (result.isConfirmed) {
                        window.showToast({
                            variant: 'info',
                            title: 'Versão arquivada',
                            message: 'A revisão anterior foi preservada para auditoria.',
                        });
                    }

                    return;
                }

                const result = await window.confirmInfo({
                    title: 'Publicar configuração?',
                    text: 'As novas regras ficarão visíveis para toda a equipe administrativa.',
                    confirmText: 'Publicar',
                });

                if (result.isConfirmed) {
                    window.showToast({
                        variant: 'success',
                        title: 'Configuração publicada',
                        message: 'A confirmação informativa foi concluída.',
                    });
                }
            });
        });

        document.querySelectorAll('[data-alert-demo]').forEach((button) => {
            button.addEventListener('click', async () => {
                const type = button.getAttribute('data-alert-demo');

                if (type === 'error') {
                    await window.alertError({
                        title: 'Falha na integração bancária',
                        text: 'O retorno CNAB não pôde ser processado. Revise o arquivo e tente novamente.',
                    });
                    return;
                }

                await window.alertSuccess({
                    title: 'Importação concluída',
                    text: 'A planilha foi processada e os formandos já estão disponíveis para conferência.',
                });
            });
        });
    </script>
@endsection
