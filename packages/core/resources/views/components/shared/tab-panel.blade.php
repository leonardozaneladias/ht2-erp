@props ([
    'id',
    'active' => false,
])

<div
    id="panel-{{ $id }}"
    role="tabpanel"
    aria-labelledby="tab-{{ $id }}"
    {{
$attributes->class([
        'hidden' => ! $active,
    ])
}}
>
    {{ $slot }}
</div>
