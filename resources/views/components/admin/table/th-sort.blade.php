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
    // aria-sort expõe o estado de ordenação a tecnologias assistivas (WCAG 4.1.2):
    // sem ele, a direção só existe visualmente (seta ↑/↓). scope="col" associa as
    // células ao cabeçalho da coluna (WCAG 1.3.1). Ambos são derivados do estado.
    $ariaSort = $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<th {{ $attributes->class(['whitespace-nowrap'])->merge(['scope' => 'col', 'aria-sort' => $ariaSort]) }}>
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
            <span
                class="iconify {{ $dir === 'asc' ? 'tabler--arrow-up' : 'tabler--arrow-down' }} size-3.5"
                aria-hidden="true"
            ></span>
        @else
            <span
                class="iconify tabler--arrows-sort size-3.5 opacity-0 transition-opacity group-hover:opacity-50"
                aria-hidden="true"
            ></span>
        @endif
    </button>
</th>
