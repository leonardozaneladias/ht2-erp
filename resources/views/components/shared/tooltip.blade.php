@props ([
    'content',
    'placement' => 'top',
])

@php
    $placement = in_array($placement, ['top', 'bottom', 'left', 'right'], true) ? $placement : 'top';
@endphp

<span {{ $attributes->class(['hs-tooltip relative inline-flex', "[--placement:{$placement}]"]) }}>
    <span class="hs-tooltip-toggle inline-flex">
        {{ $slot }}

        <span
            class="hs-tooltip-content hs-tooltip-shown:visible hs-tooltip-shown:opacity-100 bg-dark invisible absolute z-30 inline-block rounded-lg px-3 py-2 text-xs font-medium whitespace-nowrap text-white opacity-0 shadow-2xs transition-opacity"
            role="tooltip"
        >
            {{ $content }}
        </span>
    </span>
</span>
