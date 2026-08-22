{{-- Empty state padrão das tabelas PowerGrid (retornado por noDataLabel()). --}}
<x-shared.empty-state size="sm" :icon="$icon ?? 'tabler--inbox'" :title="$titulo" title-level="h5">
    @if ($descricao ?? null)
        <p>{{ $descricao }}</p>
    @endif

    @if (($ctaUrl ?? null) && ($ctaLabel ?? null))
        <div class="mt-4">
            <x-shared.button :href="$ctaUrl" variant="primary" size="sm" icon="tabler--plus" wire:navigate>
                {{ $ctaLabel }}
            </x-shared.button>
        </div>
    @endif
</x-shared.empty-state>
