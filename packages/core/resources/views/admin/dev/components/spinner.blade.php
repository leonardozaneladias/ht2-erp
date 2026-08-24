@extends ('admin.dev.components.shell', [
    'title' => 'Preview • Spinner',
    'description' => 'Indicadores leves de carregamento para wire:loading, placeholders e estados transitórios inline.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card title="Variantes" subtitle="Cores oficiais do sistema">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'] as $variant)
                    <div
                        class="border-default-300 bg-light/40 flex flex-col items-center gap-3 rounded-xl border px-4 py-5 text-center"
                    >
                        <x-shared.spinner :variant="$variant" />
                        <p class="text-body-color text-sm font-medium">{{ \Illuminate\Support\Str::headline($variant) }}</p>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <div class="space-y-6">
            <x-shared.card title="Tamanhos" subtitle="Do inline discreto ao loading de área">
                <div class="flex flex-wrap items-end gap-6">
                    @foreach (['xs', 'sm', 'md', 'lg', 'xl'] as $size)
                        <div class="flex flex-col items-center gap-2">
                            <x-shared.spinner :size="$size" />
                            <span class="text-default-400 text-xs tracking-[0.18em] uppercase">{{ $size }}</span>
                        </div>
                    @endforeach
                </div>
            </x-shared.card>

            <x-shared.card title="Casos reais" subtitle="Exemplos próximos do uso em Livewire e placeholders">
                <div class="space-y-4">
                    <div class="border-default-300 bg-light/40 flex items-center gap-3 rounded-xl border px-4 py-3">
                        <x-shared.spinner size="sm" />
                        <span class="text-body-color text-sm">Salvando alterações da configuração...</span>
                    </div>

                    <div class="border-default-300 bg-card rounded-xl border px-4 py-10">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <x-shared.spinner size="lg" variant="info" />
                            <p class="text-default-400 text-sm">Carregando dados do dashboard financeiro</p>
                        </div>
                    </div>
                </div>
            </x-shared.card>
        </div>
    </div>
@endsection
