@props ([
    'justified' => false,
])

<nav
    aria-label="Tabs"
    role="tablist"
    {{
$attributes->class([
        'flex gap-1 overflow-x-auto border-b border-default-300 pb-px dark:border-default-700',
        'md:flex-nowrap' => $justified,
        'sm:flex-wrap' => ! $justified,
    ])
}}
>
    {{ $slot }}
</nav>
