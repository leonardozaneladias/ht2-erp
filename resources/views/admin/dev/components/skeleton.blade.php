@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.skeleton',
    'description' => 'Placeholder de carregamento reutilizável para estados de loading sofisticados. Use dentro de wire:loading ou enquanto dados assíncronos chegam. Respeita prefers-reduced-motion.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Variações" subtitle="block, text e circle">
            <div class="space-y-6">
                <div>
                    <p class="text-default-400 mb-2 text-xs uppercase">Bloco</p>
                    <x-shared.skeleton class="h-24 w-full" />
                </div>

                <div>
                    <p class="text-default-400 mb-2 text-xs uppercase">Linhas de texto (:lines="3")</p>
                    <x-shared.skeleton :lines="3" />
                </div>

                <div>
                    <p class="text-default-400 mb-2 text-xs uppercase">Avatar + texto</p>
                    <div class="flex items-center gap-3">
                        <x-shared.skeleton circle class="size-12 shrink-0" />
                        <x-shared.skeleton :lines="2" class="max-w-xs" />
                    </div>
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Skeleton de card" subtitle="Composição para listas e dashboards">
            <div class="space-y-3">
                @for ($i = 0; $i < 3; $i++)
                    <div class="border-default-300 flex items-center gap-4 rounded-lg border p-4">
                        <x-shared.skeleton circle class="size-10 shrink-0" />
                        <div class="flex-1">
                            <x-shared.skeleton :lines="2" />
                        </div>
                        <x-shared.skeleton class="h-6 w-16 rounded-full" />
                    </div>
                @endfor
            </div>
        </x-shared.card>
    </div>
    <x-shared.alert variant="info" title="Como usar com Livewire" class="mt-6">
        Envolva o placeholder em <code>wire:loading</code> (com <code>.delay</code> para evitar flicker) e aponte para a
        ação que dispara o carregamento:
        <code>&lt;div wire:loading.delay wire:target="busca"&gt;&lt;x-shared.skeleton :lines="5" /&gt;&lt;/div&gt;</code
        >.
    </x-shared.alert>
@endsection
