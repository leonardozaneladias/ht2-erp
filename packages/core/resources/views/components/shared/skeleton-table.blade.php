@props ([
    // Número de linhas simuladas.
    'rows' => 5,
])

{{-- Preset de skeleton para tabelas/listas — usar como placeholder() de componentes Livewire #[Lazy]. --}}
<div {{ $attributes->class('border-default-200 rounded-lg border p-4') }} aria-hidden="true">
    <x-shared.skeleton class="mb-4 h-4 w-1/3" />
    <div class="flex flex-col gap-3">
        @for ($i = 0; $i < (int) $rows; $i++)
            <div class="flex items-center gap-3">
                <x-shared.skeleton circle class="size-8 shrink-0" />
                <x-shared.skeleton class="h-3 grow" />
                <x-shared.skeleton class="h-3 w-16 shrink-0" />
            </div>
        @endfor
    </div>
</div>
