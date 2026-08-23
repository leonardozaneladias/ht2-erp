@props ([
    'justified' => false,
])

<nav
    aria-label="Tabs"
    role="tablist"
    {{
$attributes->class([
        // `relative z-10` + `border-b`: a linha-base é a aresta superior do corpo;
        // a aba ativa sobe acima dela (via -mb-px no trigger). `mb-0!` garante que
        // a nav SEMPRE cole no conteúdo abaixo, anulando o space-y-* da view-pai
        // (no Tailwind v4 o space-y vira margin-bottom nos filhos não-últimos).
        'relative z-10 mb-0! flex gap-1 overflow-x-auto border-b border-default-300 dark:border-default-700',
        'md:flex-nowrap' => $justified,
        'sm:flex-wrap' => ! $justified,
    ])
}}
>
    {{ $slot }}
</nav>
