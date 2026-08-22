@props ([
    'searchable' => true,
    'searchModel' => 'busca',
    'searchPlaceholder' => 'Buscar...',
    'densityToggle' => true,
    'density' => 'comfortable',
])

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-1 flex-wrap items-center gap-3">
        @if ($searchable)
            <div class="input-icon-group w-full sm:w-72">
                <i class="iconify tabler--search input-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="form-input"
                    placeholder="{{ $searchPlaceholder }}"
                    wire:model.live.debounce.400ms="{{ $searchModel }}"
                    aria-label="{{ $searchPlaceholder }}"
                />
            </div>
        @endif

        {{ $filters ?? '' }}
    </div>

    <div class="flex items-center gap-2">
        {{ $actions ?? '' }}

        @if ($densityToggle)
            <button
                type="button"
                class="btn btn-icon btn-sm border-default-300 text-default-600 hover:bg-light"
                wire:click="alternarDensidade"
                aria-label="Alternar densidade da tabela"
                title="{{ $density === 'compact' ? 'Densidade confortável' : 'Densidade compacta' }}"
            >
                <span
                    class="iconify {{ $density === 'compact' ? 'tabler--baseline-density-medium' : 'tabler--baseline-density-small' }} size-4"
                    aria-hidden="true"
                ></span>
            </button>
        @endif
    </div>
</div>
