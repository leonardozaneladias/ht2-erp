@props ([
    'value',
    'label' => null,
    'copiedLabel' => 'Copiado!',
    'icon' => 'tabler--copy',
    'successIcon' => 'tabler--check',
    'successMessage' => 'Valor copiado com sucesso.',
    'errorMessage' => 'Erro ao copiar.',
    'variant' => 'default',
    'appearance' => 'outline',
    'size' => 'sm',
])

@php
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
    $sizeClasses = [
        'sm' => 'btn-sm text-xs',
        'md' => 'text-sm',
        'lg' => 'btn-lg text-sm',
    ];
    $variant = array_key_exists($variant, $solidClasses) ? $variant : 'default';
    $appearance = in_array($appearance, ['solid', 'outline', 'ghost'], true) ? $appearance : 'outline';
    $size = array_key_exists($size, $sizeClasses) ? $size : 'sm';
    $toneClasses = match ($appearance) {
        'solid' => $solidClasses[$variant],
        'ghost' => $ghostClasses[$variant],
        default => $outlineClasses[$variant],
    };
    $hasLabel = filled($label);
    $config = [
        'value' => (string) $value,
        'label' => $label,
        'copiedLabel' => $copiedLabel,
        'icon' => $icon,
        'successIcon' => $successIcon,
        'successMessage' => $successMessage,
        'errorMessage' => $errorMessage,
    ];
@endphp

<button
    type="button"
    data-af-copy='{{ \Illuminate\Support\Js::encode($config) }}'
    {{
$attributes->class([
        'btn inline-flex items-center justify-center gap-x-2 transition-all',
        $sizeClasses[$size],
        $toneClasses,
    ])
}}
    @if (! $hasLabel) aria-label="Copiar" @endif
>
    <i class="iconify {{ $icon }} text-base shrink-0" data-copy-icon aria-hidden="true"></i>

    @if ($hasLabel)
        <span data-copy-label>{{ $label }}</span>
    @endif
</button>
