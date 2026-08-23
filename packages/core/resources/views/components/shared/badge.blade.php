@props ([
    'variant' => 'primary',
    'solid' => false,
    'pill' => false,
    'size' => 'md',
    'icon' => null,
])

@php
    $variants = [
        'default' => [
            'soft' => 'border border-default-300 bg-light/70 text-default-700',
            'solid' => 'bg-default-700 text-white',
        ],
        'primary' => [
            'soft' => 'bg-primary/15 text-primary',
            'solid' => 'bg-primary text-white',
        ],
        'secondary' => [
            'soft' => 'bg-secondary/15 text-secondary',
            'solid' => 'bg-secondary text-white',
        ],
        'success' => [
            'soft' => 'bg-success/15 text-success',
            'solid' => 'bg-success text-white',
        ],
        'danger' => [
            'soft' => 'bg-danger/15 text-danger',
            'solid' => 'bg-danger text-white',
        ],
        'warning' => [
            'soft' => 'bg-warning/15 text-warning',
            'solid' => 'bg-warning text-white',
        ],
        'info' => [
            'soft' => 'bg-info/15 text-info',
            'solid' => 'bg-info text-white',
        ],
        'light' => [
            'soft' => 'bg-light text-dark',
            'solid' => 'bg-light text-dark',
        ],
        'dark' => [
            'soft' => 'bg-dark/15 text-dark',
            'solid' => 'bg-dark text-white',
        ],
    ];

    $sizes = [
        'sm' => 'px-1.5 py-0.5 text-2xs',
        'md' => null,
        'lg' => 'px-3 py-1 text-sm',
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'primary';
    $size = array_key_exists($size, $sizes) ? $size : 'md';
    $tone = $solid ? 'solid' : 'soft';
@endphp

<span
    {{
$attributes->class([
    'badge whitespace-nowrap',
    $variants[$variant][$tone],
    $sizes[$size],
    $pill ? 'rounded-full' : null,
])
}}
>
    @if ($icon)
        <i class="iconify {{ $icon }} text-sm shrink-0" aria-hidden="true"></i>
    @endif

    {{ $slot }}
</span>
