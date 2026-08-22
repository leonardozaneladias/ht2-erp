@props ([
    'variant' => 'primary',
    'appearance' => null,
    'style' => null,
    'size' => 'md',
    'pill' => false,
    'block' => false,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'type' => 'submit',
    'target' => null,
    'loadingText' => null,
    'disabled' => false,
])

@php
    $targetAttributes = $target
        ? ['wire:target' => $target]
        : [];

    $label = trim(strip_tags((string) $slot));
    $hasLabel = $label !== '';
    $loadingLabel = filled($loadingText) ? $loadingText : $label;
@endphp

<x-shared.button
    :variant="$variant"
    :appearance="$appearance"
    :style="$style"
    :size="$size"
    :pill="$pill"
    :block="$block"
    :icon-only="$iconOnly"
    :type="$type"
    :disabled="$disabled"
    {{ $attributes->merge($targetAttributes) }}
    wire:loading.attr="disabled"
>
    <span
        class="inline-flex items-center gap-x-2"
        @if ($target) wire:loading.remove wire:target="{{ $target }}" @else wire:loading.remove @endif
    >
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0" aria-hidden="true"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($iconRight && ! $iconOnly)
            <i class="iconify {{ $iconRight }} shrink-0" aria-hidden="true"></i>
        @endif
    </span>

    <span
        class="inline-flex items-center gap-x-2"
        @if ($target) wire:loading wire:target="{{ $target }}" @else wire:loading @endif
    >
        <i class="iconify tabler--loader-2 shrink-0 animate-spin motion-reduce:animate-none" aria-hidden="true"></i>

        @if ($loadingLabel !== '')
            <span @class (['sr-only' => $iconOnly])>{{ $loadingLabel }}</span>
        @endif
    </span>
</x-shared.button>
