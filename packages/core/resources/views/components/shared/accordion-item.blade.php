@props ([
    'id',
    'title',
    'open' => false,
    'icon' => null,
])

@php
    $panelId = 'accordion-panel-'.$id;
    $triggerId = 'accordion-trigger-'.$id;
@endphp

<div @class (['hs-accordion', 'active' => $open]) id="{{ $id }}">
    <button
        type="button"
        id="{{ $triggerId }}"
        class="hs-accordion-toggle hs-accordion-active:bg-light/60 text-body-color flex w-full items-center justify-between gap-3 px-5 py-4 text-start font-semibold transition"
        aria-controls="{{ $panelId }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
    >
        <span class="flex min-w-0 items-center gap-3">
            @if ($icon)
                <i class="iconify {{ $icon }} shrink-0 text-lg text-default-400" aria-hidden="true"></i>
            @endif

            <span class="truncate">{{ $title }}</span>
        </span>

        <i
            class="iconify tabler--chevron-down hs-accordion-active:rotate-180 text-default-400 shrink-0 text-base transition-transform"
            aria-hidden="true"
        ></i>
    </button>

    <div
        id="{{ $panelId }}"
        aria-labelledby="{{ $triggerId }}"
        @class ([
            'hs-accordion-content border-default-300 w-full overflow-hidden border-t transition-[height] duration-300',
            'hidden' => ! $open,
        ])
        role="region"
    >
        <div class="px-5 py-4">{{ $slot }}</div>
    </div>
</div>
