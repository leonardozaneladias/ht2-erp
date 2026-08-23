@props ([
    'href' => null,
    'icon' => null,
    'variant' => null,
    'active' => false,
    'disabled' => false,
])

@php
    $variantClasses = [
        'default' => 'text-default-700',
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
        'light' => 'text-dark',
        'dark' => 'text-dark',
    ];

    $classes = [
        'dropdown-item text-start',
        $variantClasses[$variant] ?? null,
        $active ? 'active' : null,
        $disabled ? 'pointer-events-none opacity-50' : null,
    ];
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base" aria-hidden="true"></i>
        @endif

        <span>{{ $slot }}</span>
    </a>
@elseif ($href)
    <span {{ $attributes->class($classes) }} aria-disabled="true" role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base" aria-hidden="true"></i>
        @endif

        <span>{{ $slot }}</span>
    </span>
@else
    <button type="button" {{ $attributes->class($classes) }} @disabled ($disabled) role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base" aria-hidden="true"></i>
        @endif

        <span>{{ $slot }}</span>
    </button>
@endif
