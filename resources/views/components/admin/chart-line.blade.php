@props ([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'height' => 320,
    'series' => [],
    'categories' => [],
    'colors' => ['--color-success', '--color-danger'],
    'options' => [],
])

@php
    $resolvedChartId = $chartId ?: 'chart-line-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $config = [
        'type' => 'line',
        'height' => (int) $height,
        'series' => $series,
        'categories' => $categories,
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
