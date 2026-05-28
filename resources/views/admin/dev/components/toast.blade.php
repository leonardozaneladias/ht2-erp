@extends ('admin.dev.components.shell', [
    'title' => 'Preview • família x-shared.toast',
    'description' => 'Toasts efêmeros com container fixo e helper JS leve para browser events e Livewire.',
])

@php
    $successPayload = \Illuminate\Support\Js::encode([
        'variant' => 'success',
        'title' => 'Parcelas baixadas',
        'message' => '24 parcelas foram conciliadas com sucesso.',
    ]);

    $persistentPayload = \Illuminate\Support\Js::encode([
        'variant' => 'info',
        'title' => 'Processamento em fila',
        'message' => 'A exportação do relatório começou e pode levar alguns minutos.',
        'duration' => false,
    ]);
@endphp

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Disparos rápidos" subtitle="As ações abaixo usam o helper real do lote">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([
                    ['variant' => 'success', 'label' => 'Sucesso', 'title' => 'Contrato atualizado', 'message' => 'Os dados foram salvos e a turma foi sincronizada.'],
                    ['variant' => 'danger', 'label' => 'Erro', 'title' => 'Falha no gateway', 'message' => 'Não foi possível reemitir o boleto agora.'],
                    ['variant' => 'warning', 'label' => 'Atenção', 'title' => 'Reajuste pendente', 'message' => 'Existem 12 parcelas aguardando confirmação.'],
                    ['variant' => 'info', 'label' => 'Informação', 'title' => 'Importação em andamento', 'message' => 'Os novos formandos aparecerão assim que o job terminar.'],
                ] as $example)
                    <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                        <p class="text-body-color mb-3 text-sm font-medium">{{ $example['title'] }}</p>

                        <div class="flex flex-wrap gap-3">
                            <x-shared.button
                                :variant="$example['variant'] === 'danger' ? 'danger' : ($example['variant'] === 'warning' ? 'warning' : ($example['variant'] === 'success' ? 'success' : 'info'))"
                                :icon="$example['variant'] === 'danger' ? 'tabler--alert-octagon' : ($example['variant'] === 'warning' ? 'tabler--alert-triangle' : ($example['variant'] === 'success' ? 'tabler--circle-check' : 'tabler--info-circle'))"
                                :data-toast-demo="\Illuminate\Support\Js::encode($example)"
                            >
                                Disparar {{ $example['label'] }}
                            </x-shared.button>

                            <x-shared.toast
                                :variant="$example['variant']"
                                :title="$example['title']"
                                :message="$example['message']"
                                class="max-w-none"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Fluxo financeiro">
            <div class="space-y-4">
                <div class="border-default-300 bg-card rounded-2xl border p-4">
                    <p class="text-body-color font-semibold">Baixa em lote concluída</p>
                    <p class="text-default-400 mt-1 text-sm">Ao finalizar uma ação crítica, a tela pode disparar um toast curto sem interromper a continuidade do trabalho.</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-shared.button
                            variant="primary"
                            icon="tabler--cash-banknote"
                            :data-toast-demo="$successPayload"
                        >
                            Simular baixa em lote
                        </x-shared.button>

                        <x-shared.button
                            variant="default"
                            appearance="outline"
                            icon="tabler--clock"
                            :data-toast-demo="$persistentPayload"
                        >
                            Toast persistente
                        </x-shared.button>
                    </div>
                </div>

                <x-shared.alert variant="info" title="Observação">
                    O container fica global no layout admin e escuta
                    <code>window.dispatchEvent(new CustomEvent('toast', ...))</code>.
                </x-shared.alert>
            </div>
        </x-shared.card>
    </div>
@endsection

@section ('previewScripts')
    <script>
        document.querySelectorAll('[data-toast-demo]').forEach((button) => {
            button.addEventListener('click', () => {
                const payload = JSON.parse(button.getAttribute('data-toast-demo'));
                window.showToast(payload);
            });
        });
    </script>
@endsection
