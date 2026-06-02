@props ([
    'column',
    'sort' => null,
    'dir' => 'asc',
    'align' => 'start',
])

@php
    $isActive = $sort === $column;
    $alignClass = match ($align) {
        'end' => 'justify-end text-end',
        'center' => 'justify-center text-center',
        default => 'justify-start text-start',
    };
@endphp

<th {{ $attributes->class(['whitespace-nowrap']) }}>
    <button
        type="button"
        wire:click="ordenarPor('{{ $column }}')"
        @class ([
            'group inline-flex w-full items-center gap-1.5 font-semibold transition-colors',
            'hover:text-primary',
            'text-primary' => $isActive,
            $alignClass,
        ])
    >
        <span>{{ $slot }}</span>
        @if ($isActive)
            <span class="iconify {{ $dir === 'asc' ? 'tabler--arrow-up' : 'tabler--arrow-down' }} size-3.5"></span>
        @else
            <span
                class="iconify tabler--arrows-sort size-3.5 opacity-0 transition-opacity group-hover:opacity-50"
            ></span>
        @endif
    </button>
</th>
