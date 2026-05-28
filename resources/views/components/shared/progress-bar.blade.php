@props ([
    'value' => 0,
    'variant' => 'primary',
    'size' => 'md',
    'label' => null,
    'striped' => false,
    'animated' => false,
    'autoColor' => false,
])

@php
    $pct = max(0, min(100, (float) $value));

    if ($autoColor) {
        $variant = match (true) {
            $pct >= 80 => 'success',
            $pct >= 50 => 'warning',
            default => 'danger',
        };
    }

    $sizeMap = [
        'sm' => ['track' => 'h-1.5', 'fill' => 'text-[10px]'],
        'md' => ['track' => 'h-2.5', 'fill' => 'text-[11px]'],
        'lg' => ['track' => 'h-4', 'fill' => 'text-xs'],
    ];

    $variantMap = [
        'primary' => 'bg-primary text-white',
        'success' => 'bg-success text-white',
        'warning' => 'bg-warning text-white',
        'danger' => 'bg-danger text-white',
        'info' => 'bg-info text-white',
        'default' => 'bg-default-400 text-white',
    ];

    $resolvedSize = $sizeMap[$size] ?? $sizeMap['md'];
    $fillClasses = [
        $variantMap[$variant] ?? $variantMap['primary'],
        $resolvedSize['fill'],
        'relative flex h-full items-center justify-center rounded-full px-2 font-semibold whitespace-nowrap transition-all duration-500',
    ];

    if ($striped) {
        $fillClasses[] = 'bg-stripes';
    }

    if ($animated && $striped) {
        $fillClasses[] = 'animate-stripes';
    }

    $hasSlotContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
    $labelText = $hasSlotContent
        ? null
        : ($label === true ? round($pct).'%' : (is_string($label) ? $label : null));
@endphp

<div
    {{
$attributes->class([
        'flex w-full overflow-hidden rounded-full bg-light/60',
        $resolvedSize['track'],
    ])
}}
    role="progressbar"
    aria-valuenow="{{ round($pct, 2) }}"
    aria-valuemin="0"
    aria-valuemax="100"
>
    <div class="{{ implode(' ', $fillClasses) }}" style="width: {{ $pct }}%">
        @if ($hasSlotContent)
            {{ $slot }}
        @elseif ($labelText)
            {{ $labelText }}
        @endif
    </div>
</div>
