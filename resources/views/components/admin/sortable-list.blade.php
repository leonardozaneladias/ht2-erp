@props ([
    'id' => null,
    'target' => null,
    'handle' => '.drag-handle',
    'animation' => 150,
    'ghostClass' => 'bg-primary/10',
])

@php
    $sortableId = $id ?: 'sortable-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $config = [
        'target' => $target,
        'handle' => $handle,
        'animation' => (int) $animation,
        'ghostClass' => $ghostClass,
    ];
@endphp

<div
    id="{{ $sortableId }}"
    {{ $attributes->class(['space-y-0']) }}
    data-af-sortable="{{ \Illuminate\Support\Js::encode($config) }}"
    wire:ignore
>
    {{ $slot }}
</div>
