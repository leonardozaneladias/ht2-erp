@props ([
    'variant' => 'primary',
    'size' => 'md',
    'label' => 'Carregando...',
])

@php
    $variantClasses = [
        'default' => 'border-default-300',
        'primary' => 'border-primary',
        'secondary' => 'border-secondary',
        'success' => 'border-success',
        'danger' => 'border-danger',
        'warning' => 'border-warning',
        'info' => 'border-info',
        'light' => 'border-light',
        'dark' => 'border-dark',
    ];

    $sizeClasses = [
        'xs' => 'size-3 border-2',
        'sm' => 'size-4 border-2',
        'md' => 'size-6 border-[3px]',
        'lg' => 'size-8 border-[3px]',
        'xl' => 'size-12 border-4',
    ];

    $variant = array_key_exists($variant, $variantClasses) ? $variant : 'primary';
    $size = array_key_exists($size, $sizeClasses) ? $size : 'md';
@endphp

<div
    {{
$attributes->class([
        'inline-block shrink-0 animate-spin rounded-full border-t-transparent',
        $variantClasses[$variant],
        $sizeClasses[$size],
    ])
}}
    role="status"
    aria-label="{{ $label }}"
>
    <span class="sr-only">{{ $label }}</span>
</div>
