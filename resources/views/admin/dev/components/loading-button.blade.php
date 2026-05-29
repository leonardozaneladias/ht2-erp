@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.loading-button',
    'description' => 'Wrapper Livewire-native sobre a base de button, com simulação visual de loading para o ambiente de preview.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <x-shared.card title="Variações" subtitle="Mesmo contrato visual de x-shared.button">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([
                    ['variant' => 'primary', 'label' => 'Salvar registro', 'target' => 'salvarRegistro', 'icon' => 'tabler--device-floppy'],
                    ['variant' => 'danger', 'label' => 'Cancelar parcela', 'target' => 'cancelarParcela', 'icon' => 'tabler--ban'],
                    ['variant' => 'default', 'label' => 'Gerar relatório', 'target' => 'gerarRelatorio', 'icon' => 'tabler--file-export', 'appearance' => 'outline'],
                    ['variant' => 'success', 'label' => 'Aplicar reajuste', 'target' => 'aplicarReajuste', 'icon' => 'tabler--sparkles'],
                ] as $example)
                    <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                        <p class="text-body-color mb-3 text-sm font-medium">{{ $example['label'] }}</p>

                        <div class="flex flex-wrap gap-3">
                            <x-shared.loading-button
                                :variant="$example['variant']"
                                :appearance="$example['appearance'] ?? null"
                                :icon="$example['icon']"
                                :target="$example['target']"
                                :loading-text="'Processando...'"
                                data-loading-preview
                            >
                                {{ $example['label'] }}
                            </x-shared.loading-button>

                            <x-shared.button
                                variant="default"
                                appearance="ghost"
                                size="sm"
                                data-loading-toggle="{{ $example['target'] }}"
                            >
                                Simular loading
                            </x-shared.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Ações financeiras">
            <div class="space-y-4">
                <div class="border-default-300 bg-card rounded-2xl border p-4">
                    <p class="text-body-color font-semibold">Baixa manual de parcela</p>
                    <p class="text-default-400 mt-1 text-sm">A ação precisa bloquear o botão durante a chamada Livewire para evitar dupla submissão.</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-shared.loading-button
                            variant="primary"
                            icon="tabler--cash-banknote"
                            target="darBaixa"
                            loading-text="Baixando parcela..."
                            data-loading-preview
                        >
                            Dar baixa manual
                        </x-shared.loading-button>

                        <x-shared.button
                            variant="default"
                            appearance="outline"
                            size="sm"
                            data-loading-toggle="darBaixa"
                        >
                            Testar estado
                        </x-shared.button>
                    </div>
                </div>

                <x-shared.alert variant="info" title="Observação">
                    No fluxo real, o estado loading é controlado pelo próprio Livewire via <code>wire:loading</code> e
                    <code>wire:target</code>. Nesta preview, o toggle acima apenas simula a transição visual.
                </x-shared.alert>
            </div>
        </x-shared.card>
    </div>
@endsection

@section ('previewScripts')
    <script>
        document.querySelectorAll('[data-loading-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-loading-toggle');
                const previewButton = document.querySelector(`[data-loading-preview][wire\\:target="${target}"]`);

                if (!previewButton) {
                    return;
                }

                const isLoading = previewButton.dataset.previewState === 'loading';
                previewButton.dataset.previewState = isLoading ? 'idle' : 'loading';
                previewButton.toggleAttribute('disabled', !isLoading);

                previewButton.querySelectorAll('[wire\\:loading]').forEach((element) => {
                    element.style.display = isLoading ? 'none' : '';
                });

                previewButton.querySelectorAll('[wire\\:loading\\.remove]').forEach((element) => {
                    element.style.display = isLoading ? '' : 'none';
                });
            });
        });
    </script>
@endsection
