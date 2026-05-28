@props ([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'height' => 350,
])

@php
    $resolvedChartId = $chartId ?: 'chart-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $hasBodyContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
@endphp

<x-shared.card {{ $attributes }} :title="$title" :subtitle="$subtitle">
    @isset ($headerActions)
        <x-slot:headerActions>
            {{ $headerActions }}
        </x-slot:headerActions>
    @endisset

    <div class="space-y-4">
        @isset ($chart)
            <div class="border-default-200/80 bg-light/30 overflow-hidden rounded-xl border p-3" dir="ltr">
                {{ $chart }}
            </div>
        @else
            <div
                id="{{ $resolvedChartId }}"
                class="border-default-200/80 bg-light/30 overflow-hidden rounded-xl border"
                style="min-height: {{ (int) $height }}px"
                data-af-chart-host
                wire:ignore
            ></div>
        @endisset

        @if ($hasBodyContent)
            <div class="text-default-400 text-sm">{{ $slot }}</div>
        @endif
    </div>
</x-shared.card>
