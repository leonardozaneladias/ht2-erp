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
@endphp

<li class="bg-card">
    @if ($href)
        <a href="{{ $href }}" {{ $attributes->class($itemClasses) }}> {{ $slot }} </a>
    @else
        <div {{ $attributes->class($itemClasses) }}> {{ $slot }}</div>
    @endif
</li>
