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
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $allowedVariants = ['default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    $allowedAppearances = ['solid', 'outline', 'ghost'];
    $allowedSizes = ['sm', 'md', 'lg'];

    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'primary';
    $appearance = $appearance ?? $style ?? 'solid';
    $appearance = in_array($appearance, $allowedAppearances, true) ? $appearance : 'solid';
    $size = in_array($size, $allowedSizes, true) ? $size : 'md';

    $solidClasses = [
        'default' => 'border-default-300 bg-card text-default-700 hover:bg-light',
        'primary' => 'bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-hover',
        'success' => 'bg-success text-white hover:bg-success-hover',
        'danger' => 'bg-danger text-white hover:bg-danger-hover',
        'warning' => 'bg-warning text-white hover:bg-warning-hover',
        'info' => 'bg-info text-white hover:bg-info-hover',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark text-white hover:bg-dark-hover',
    ];

    $outlineClasses = [
        'default' => 'border-default-300 text-default-700 hover:bg-light',
        'primary' => 'border-primary text-primary hover:bg-primary hover:text-white',
        'secondary' => 'border-secondary text-secondary hover:bg-secondary hover:text-white',
        'success' => 'border-success text-success hover:bg-success hover:text-white',
        'danger' => 'border-danger text-danger hover:bg-danger hover:text-white',
        'warning' => 'border-warning text-warning hover:bg-warning hover:text-white',
        'info' => 'border-info text-info hover:bg-info hover:text-white',
        'light' => 'border-light text-dark hover:bg-light hover:text-dark',
        'dark' => 'border-dark text-dark hover:bg-dark hover:text-white',
    ];

    $ghostClasses = [
        'default' => 'text-default-700 hover:bg-light',
        'primary' => 'text-primary hover:bg-primary/15',
        'secondary' => 'text-secondary hover:bg-secondary/15',
        'success' => 'text-success hover:bg-success/15',
        'danger' => 'text-danger hover:bg-danger/15',
        'warning' => 'text-warning hover:bg-warning/15',
        'info' => 'text-info hover:bg-info/15',
        'light' => 'text-dark hover:bg-light',
        'dark' => 'text-dark hover:bg-dark/15',
    ];

    $variantClasses = match ($appearance) {
        'outline' => $outlineClasses[$variant],
        'ghost' => $ghostClasses[$variant],
        default => $solidClasses[$variant],
    };

    $sizeClasses = [
        'sm' => 'btn-sm',
        'md' => null,
        'lg' => 'btn-lg',
    ];

    $iconSizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];

    $label = trim(strip_tags((string) $slot));
    $hasLabel = $label !== '';

    $classes = [
        'btn',
        $variantClasses,
        $sizeClasses[$size],
        $pill ? 'rounded-full' : null,
        $block ? 'w-full' : null,
        $iconOnly ? 'btn-icon' : null,
        'inline-flex items-center justify-center transition-all',
        $iconOnly ? 'gap-0' : 'gap-x-2',
        $disabled ? 'pointer-events-none opacity-50' : null,
    ];

    $elementAttributes = $attributes->class($classes);

    if ($iconOnly && $hasLabel && ! $attributes->has('aria-label')) {
        $elementAttributes = $elementAttributes->merge(['aria-label' => $label]);
    }

    $leadingIcon = $icon ?: ($iconOnly ? $iconRight : null);
    $trailingIcon = $iconOnly ? null : $iconRight;
@endphp

@if ($href)
    <a
        @if (! $disabled) href="{{ $href }}" @endif
        {{ $elementAttributes }}
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        @if ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $elementAttributes }} @disabled ($disabled)>
        @if ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </button>
@endif
