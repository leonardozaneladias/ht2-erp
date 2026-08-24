<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card
            title="Notificações (toasts)"
            subtitle="Mensagens rápidas de feedback, como “Salvo com sucesso”. Ajuste e clique em Testar para pré-visualizar."
        >
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.select-search
                    name="toast_posicao"
                    label="Posição na tela"
                    wire:model.live="toast_posicao"
                    :value="$toast_posicao"
                    :placeholder="null"
                    :options="$posicoes"
                    hint="Onde as notificações aparecem."
                />
                <x-shared.select-search
                    name="toast_duracao"
                    label="Duração"
                    wire:model.live="toast_duracao"
                    :value="$toast_duracao"
                    :placeholder="null"
                    :options="$duracoes"
                    hint="Tempo até sumir sozinha (erros duram um pouco mais)."
                />
                <x-shared.select-search
                    name="toast_estilo"
                    label="Estilo"
                    wire:model.live="toast_estilo"
                    :value="$toast_estilo"
                    :placeholder="null"
                    :options="$estilos"
                    hint="Pílula compacta ou card com título."
                />
                <x-shared.select-search
                    name="toast_maximo"
                    label="Máximo na tela"
                    wire:model.live="toast_maximo"
                    :value="$toast_maximo"
                    :placeholder="null"
                    :options="$opcoesMaximo"
                    hint="Quantas notificações ao mesmo tempo."
                />
            </div>

            <div class="border-default-200 mt-2 flex flex-wrap items-center gap-3 border-t pt-4">
                <button
                    type="button"
                    x-data
                    x-on:click="
                        window.notifyConfigure({
                            position: $wire.toast_posicao,
                            durationKey: $wire.toast_duracao,
                            style: $wire.toast_estilo,
                            max: $wire.toast_maximo,
                        });
                        window.notify('success', 'Tudo certo! Suas alterações foram salvas.', {
                            title: $wire.toast_estilo === 'card' ? 'Sucesso' : null,
                        });
                        window.setTimeout(
                            () =>
                                window.notify('info', 'Esta é uma notificação informativa.', {
                                    title: $wire.toast_estilo === 'card' ? 'Informação' : null,
                                }),
                            600,
                        );
                    "
                    class="btn bg-primary hover:bg-primary-hover cursor-pointer text-white"
                >
                    <i class="iconify tabler--bell-ringing me-1.5 text-base"></i>
                    Testar notificação
                </button>
                <span class="text-default-400 text-xs">Pré-visualize sem salvar.</span>
            </div>
        </x-shared.card>

        <x-shared.card
            title="Confirmações"
            subtitle="Diálogos de “tem certeza?” exibidos antes de ações destrutivas (ex.: excluir)."
        >
            <div class="md:max-w-xs">
                <x-shared.select-search
                    name="confirmacao_posicao"
                    label="Posição do diálogo"
                    wire:model.live="confirmacao_posicao"
                    :value="$confirmacao_posicao"
                    :placeholder="null"
                    :options="$posicoesConfirmacao"
                    hint="Onde a janela de confirmação aparece."
                />
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    x-data
                    x-on:click="
                        window.confirmInfo({
                            title: 'Exemplo de confirmação',
                            text: 'É assim que as confirmações vão aparecer para o usuário.',
                            position: $wire.confirmacao_posicao,
                            confirmText: 'Entendi',
                            cancelText: 'Fechar',
                        });
                    "
                    class="btn bg-light text-body-color hover:bg-light-hover dark:bg-default-900 dark:text-default-100 cursor-pointer"
                >
                    <i class="iconify tabler--eye me-1.5 text-base"></i>
                    Testar confirmação
                </button>
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar notificações
            </x-shared.loading-button>
        </div>
    </form>
</div>
