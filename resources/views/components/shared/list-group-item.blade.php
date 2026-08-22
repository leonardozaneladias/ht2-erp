@props ([
    'href' => null,
    'active' => false,
])

@php
    $itemClasses = [
        'px-4 py-3 text-sm text-body-color transition',
        'block hover:bg-light/70 hover:text-primary' => filled($href),
        'bg-primary/8 text-primary font-semibold' => $active,
    ];

    // Item ativo anuncia o estado "atual" a leitores de tela (WAI-ARIA aria-current),
    // como já fazem a sidebar e o breadcrumb. Com href (navegação) usa "page"; sem href
    // (item estático selecionado) usa "true". O merge mantém o default sobrescrevível
    // pelo consumidor (ex.: aria-current="step").
    $itemAttributes = $attributes->class($itemClasses);

    if ($active) {
        $itemAttributes = $itemAttributes->merge([
            'aria-current' => filled($href) ? 'page' : 'true',
        ]);
    }
@endphp

<li class="bg-card">
    @if ($href)
        <a href="{{ $href }}" {{ $itemAttributes }}> {{ $slot }} </a>
    @else
        <div {{ $itemAttributes }}> {{ $slot }}</div>
    @endif
</li>
