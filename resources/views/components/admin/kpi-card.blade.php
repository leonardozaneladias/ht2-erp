@props ([
    'label',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'trendLabel' => 'vs. período anterior',
    'href' => null,
])

@php
    $tag = filled($href) ? 'a' : 'div';
    $trendValue = is_numeric($trend) ? (float) $trend : null;
    $trendPositive = $trendValue !== null ? $trendValue >= 0 : null;

    $colorMap = [
        'primary' => ['soft' => 'bg-primary/12 text-primary', 'accent' => 'text-primary'],
        'success' => ['soft' => 'bg-success/12 text-success', 'accent' => 'text-success'],
        'warning' => ['soft' => 'bg-warning/12 text-warning', 'accent' => 'text-warning'],
        'danger' => ['soft' => 'bg-danger/12 text-danger', 'accent' => 'text-danger'],
        'info' => ['soft' => 'bg-info/12 text-info', 'accent' => 'text-info'],
        'default' => ['soft' => 'bg-light text-default-500', 'accent' => 'text-default-500'],
    ];

    $palette = $colorMap[$color] ?? $colorMap['primary'];
    $trendClasses = $trendPositive === null
        ? 'text-default-400'
        : ($trendPositive ? 'text-success' : 'text-danger');
    $trendIcon = $trendPositive === null
        ? 'tabler--minus'
        : ($trendPositive ? 'tabler--trending-up' : 'tabler--trending-down');
    $trendPrefix = $trendPositive ? '+' : '';
    $hasExtraContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{
$attributes->class([
        'card block transition',
        'hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30' => filled($href),
    ])
}}
>
    <div class="card-body">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-default-400 text-2xs font-semibold tracking-[0.22em] uppercase">{{ $label }}</p>
                <p class="text-body-color mt-2 truncate text-2xl font-semibold">{{ $value }}</p>

                @if ($trendValue !== null)
                    <div class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium {{ $trendClasses }}">
                        <i class="iconify {{ $trendIcon }} text-sm"></i>
                        <span>{{ $trendPrefix }}{{ number_format($trendValue, 1, ',', '.') }}%</span>
                        <span class="text-default-400">{{ $trendLabel }}</span>
                    </div>
                @endif
            </div>

            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $palette['soft'] }}">
                <i class="iconify {{ $icon }} text-2xl"></i>
            </span>
        </div>

        @if ($hasExtraContent)
            <div class="border-default-200/80 text-default-400 mt-4 border-t pt-4 text-sm">{{ $slot }}</div>
        @endif
    </div>
</{{ $tag }}>
