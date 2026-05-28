@props ([
    'flush' => false,
])

<div
    {{
$attributes->class([
        'hs-accordion-group overflow-hidden',
        'divide-default-300 rounded-xl border border-default-300 divide-y bg-card' => ! $flush,
    ])
}}
>
    {{ $slot }}
</div>
