@props ([
    'id' => null,
    'target' => null,
    'handle' => '.drag-handle',
    'animation' => 150,
    'ghostClass' => 'bg-primary/10',
    // Nome do group do SortableJS: listas com o mesmo group aceitam drag
    // entre si (o método target recebe item, container destino e ordens).
    'group' => null,
    // Identificador do container reportado no payload cross-list.
    'containerId' => null,
    // Desligue (false) quando a lista é re-renderizada pelo Livewire — use
    // wire:key por linha nesse caso.
    'wireIgnore' => true,
])

@php
    $sortableId = $id ?: 'sortable-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $config = [
        'target' => $target,
        'handle' => $handle,
        'animation' => (int) $animation,
        'ghostClass' => $ghostClass,
        'group' => $group,
    ];
@endphp

<div
    id="{{ $sortableId }}"
    {{ $attributes->class(['space-y-0']) }}
    data-af-sortable="{{ \Illuminate\Support\Js::encode($config) }}"
    data-af-container="{{ $containerId ?? $sortableId }}"
    @if ($wireIgnore) wire:ignore @endif
>
    {{ $slot }}
</div>
