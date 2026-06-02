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
    'loading' => false,
])

@php
    $allowedVariants = ['default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    $allowedAppearances = ['solid', 'outline', 'ghost', 'soft'];
    $allowedSizes = ['sm', 'md', 'lg'];

    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'primary';
    $appearance = $appearance ?? $style ?? 'solid';
    $appearance = in_array($appearance, $allowedAppearances, true) ? $appearance : 'solid';
    $size = in_array($size, $allowedSizes, true) ? $size : 'md';

    $solidClasses = [
        'default' => 'border-default-300 bg-card text-default-700 shadow-sm hover:bg-light hover:border-default-400',
        'primary' => 'bg-primary text-white shadow-sm shadow-primary/25 hover:bg-primary-hover',
        'secondary' => 'bg-secondary text-white shadow-sm shadow-secondary/25 hover:bg-secondary-hover',
        'success' => 'bg-success text-white shadow-sm shadow-success/25 hover:bg-success-hover',
        'danger' => 'bg-danger text-white shadow-sm shadow-danger/25 hover:bg-danger-hover',
        'warning' => 'bg-warning text-white shadow-sm shadow-warning/25 hover:bg-warning-hover',
        'info' => 'bg-info text-white shadow-sm shadow-info/25 hover:bg-info-hover',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark text-white shadow-sm shadow-dark/25 hover:bg-dark-hover',
    ];

    $outlineClasses = [
        'default' => 'border-default-300 text-default-700 hover:bg-light hover:border-default-400',
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
        'primary' => 'text-primary hover:bg-primary/10',
        'secondary' => 'text-secondary hover:bg-secondary/10',
        'success' => 'text-success hover:bg-success/10',
        'danger' => 'text-danger hover:bg-danger/10',
        'warning' => 'text-warning hover:bg-warning/10',
        'info' => 'text-info hover:bg-info/10',
        'light' => 'text-dark hover:bg-light',
        'dark' => 'text-dark hover:bg-dark/10',
    ];

    $softClasses = [
        'default' => 'bg-light text-default-700 hover:bg-light-hover',
        'primary' => 'bg-primary/12 text-primary hover:bg-primary/20',
        'secondary' => 'bg-secondary/12 text-secondary hover:bg-secondary/20',
        'success' => 'bg-success/12 text-success hover:bg-success/20',
        'danger' => 'bg-danger/12 text-danger hover:bg-danger/20',
        'warning' => 'bg-warning/15 text-warning hover:bg-warning/25',
        'info' => 'bg-info/12 text-info hover:bg-info/20',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark/10 text-dark hover:bg-dark/20',
    ];

    $ringColors = [
        'default' => 'focus-visible:ring-default-400/40',
        'primary' => 'focus-visible:ring-primary/40',
        'secondary' => 'focus-visible:ring-secondary/40',
        'success' => 'focus-visible:ring-success/40',
        'danger' => 'focus-visible:ring-danger/40',
        'warning' => 'focus-visible:ring-warning/40',
        'info' => 'focus-visible:ring-info/40',
        'light' => 'focus-visible:ring-default-400/40',
        'dark' => 'focus-visible:ring-dark/40',
    ];

    $variantClasses = match ($appearance) {
        'outline' => $outlineClasses[$variant],
        'ghost' => $ghostClasses[$variant],
        'soft' => $softClasses[$variant],
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
    $isInactive = $disabled || $loading;

    $classes = [
        'btn',
        $variantClasses,
        $sizeClasses[$size],
        $ringColors[$variant],
        $pill ? 'rounded-full' : null,
        $block ? 'w-full' : null,
        $iconOnly ? 'btn-icon' : null,
        'inline-flex items-center justify-center transition-all duration-150 ease-out',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-offset-card',
        'active:scale-[0.97]',
        $iconOnly ? 'gap-0' : 'gap-x-2',
        $isInactive ? 'pointer-events-none opacity-60' : null,
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
        @if (! $isInactive) href="{{ $href }}" @endif
        {{ $elementAttributes }}
        @if ($isInactive) aria-disabled="true" tabindex="-1" @endif
    >
        @if ($loading)
            <span class="iconify tabler--loader-2 {{ $iconSizeClasses[$size] }} shrink-0 animate-spin"></span>
        @elseif ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon && ! $loading)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $elementAttributes }} @disabled ($isInactive)>
        @if ($loading)
            <span class="iconify tabler--loader-2 {{ $iconSizeClasses[$size] }} shrink-0 animate-spin"></span>
        @elseif ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon && ! $loading)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </button>
@endif
