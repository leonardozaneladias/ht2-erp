@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.reveal',
    'description' => 'Entrada padrão (fade-up 300ms) de blocos renderizados condicionalmente — troca de etapa de wizard, resultados, relatórios. Respeita prefers-reduced-motion.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card
            title="Entrada simples"
            subtitle="Recarregue a página para ver a animação — ela roda só quando o nó entra no DOM"
        >
            <x-shared.reveal>
                <div class="border-default-300 bg-light/40 rounded-lg border border-dashed p-6 text-center">
                    <p class="text-body-color text-sm font-medium">Bloco com entrada fade-up</p>
                    <p class="text-default-400 mt-1 text-xs">Em telas reais, envolve o conteúdo de cada etapa do fluxo.</p>
                </div>
            </x-shared.reveal>
        </x-shared.card>

        <x-shared.card
            title="Stagger em cascata"
            subtitle="A prop :delay atrasa a entrada — itens de um grid entram em sequência"
        >
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['Primeiro', 'Segundo', 'Terceiro'] as $i => $rotulo)
                    <x-shared.reveal :delay="$i * 120">
                        <div class="border-default-300 bg-light/40 rounded-lg border p-4 text-center">
                            <p class="text-body-color text-sm font-medium">{{ $rotulo }}</p>
                            <p class="text-default-400 mt-1 text-xs">delay {{ $i * 120 }}ms</p>
                        </div>
                    </x-shared.reveal>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Interativo" subtitle="Alterne o bloco para ver a entrada repetir (DOM novo a cada mount)">
            <div x-data="{ visivel: true }">
                <x-shared.button
                    type="button"
                    appearance="outline"
                    x-on:click="
                        visivel = false;
                        $nextTick(() => (visivel = true));
                    "
                >
                    Reexecutar entrada
                </x-shared.button>
                <template x-if="visivel">
                    <div>
                        <x-shared.reveal class="mt-4">
                            <div class="border-primary/30 bg-primary/5 rounded-lg border p-6 text-center">
                                <p class="text-body-color text-sm font-medium">Entrei de novo 👋</p>
                            </div>
                        </x-shared.reveal>
                    </div>
                </template>
            </div>
        </x-shared.card>
    </div>
@endsection
