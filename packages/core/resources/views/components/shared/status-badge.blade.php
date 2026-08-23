@props ([
    'enum' => null,
    'withIcon' => false,
    'pill' => true,
    'solid' => false,
    'size' => 'md',
])

@php
    $rawValue = $enum instanceof \BackedEnum ? $enum->value : $enum;
    $label = is_object($enum) && method_exists($enum, 'label')
        ? $enum->label()
        : \Illuminate\Support\Str::headline((string) $rawValue);

    $variant = is_object($enum) && method_exists($enum, 'color')
        ? $enum->color()
        : 'default';

    $icon = $withIcon && is_object($enum) && method_exists($enum, 'icon')
        ? $enum->icon()
        : null;
@endphp

<x-shared.badge :variant="$variant" :pill="$pill" :solid="$solid" :size="$size" :icon="$icon" {{ $attributes }}>
    {{ $label }}
</x-shared.badge>
