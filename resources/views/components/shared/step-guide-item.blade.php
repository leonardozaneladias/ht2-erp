@props ([
    'title',
    // Ícone tabler do nó; sem ícone o nó mostra o número informado em index.
    'icon' => null,
    'index' => null,
    // Último passo não desenha a linha conectora até o próximo.
    'last' => false,
])

<li
    @class ([
        'relative flex gap-3',
        'pb-5' => ! $last,
        // Linha conectora: do pé do nó (size-9) até o nó do passo seguinte.
        "before:bg-default-200 before:absolute before:start-4.5 before:top-10 before:bottom-1 before:w-px before:content-['']" => ! $last,
    ])
>
    <span class="bg-primary/10 text-primary z-1 flex size-9 shrink-0 items-center justify-center rounded-full">
        @if ($icon)
            <span class="iconify {{ $icon }} size-4.5" aria-hidden="true"></span>
        @else
            <span class="text-sm font-semibold">{{ $index }}</span>
        @endif
    </span>

    <div class="min-w-0 pt-1">
        <p class="text-body-color text-sm font-medium">{{ $title }}</p>

        @if ($slot->isNotEmpty())
            <div class="text-default-400 mt-0.5 text-xs">{{ $slot }}</div>
        @endif

        @isset ($action)
            <div class="mt-2.5">{{ $action }}</div>
        @endisset
    </div>
</li>
