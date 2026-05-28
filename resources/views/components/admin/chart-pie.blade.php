@props ([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'height' => 320,
    'series' => [],
    'labels' => [],
    'colors' => ['--color-primary', '--color-success', '--color-warning', '--color-info'],
    'type' => 'donut',
    'options' => [],
])

@php
    $resolvedChartId = $chartId ?: 'chart-pie-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $config = [
        'type' => $type,
        'height' => (int) $height,
        'series' => $series,
        'labels' => $labels,
        'colors' => $colors,
        'options' => $options,
    ];
@endphp

<x-admin.chart-card
    :title="$title"
    :subtitle="$subtitle"
    :chart-id="$resolvedChartId"
    :height="$height"
    {{ $attributes }}
>
    @isset ($headerActions)
        <x-slot:headerActions>
            {{ $headerActions }}
        </x-slot:headerActions>
    @endisset

    <x-slot:chart>
        <div
            id="{{ $resolvedChartId }}"
            style="min-height: {{ (int) $height }}px"
            data-af-chart='{{ \Illuminate\Support\Js::encode($config) }}'
            wire:ignore
        ></div>
    </x-slot:chart>

    {{ $slot }}
</x-admin.chart-card>
