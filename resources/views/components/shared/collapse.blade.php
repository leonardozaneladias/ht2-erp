@props ([
    'id',
    'open' => false,
])

@php
    $panelAttributes = $attributes->class([
        'hs-collapse',
        $open ? 'open' : 'hidden',
        'w-full overflow-hidden transition-[height] duration-300',
    ])->merge([
        'id' => $id,
    ]);
@endphp

@isset ($trigger)
    {{ $trigger }}
@endisset

<div {{ $panelAttributes }}> {{ $slot }}</div>
