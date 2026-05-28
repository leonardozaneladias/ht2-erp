@props ([
    'id',
    'active' => false,
    'disabled' => false,
    'icon' => null,
    'justified' => false,
])

<button
    type="button"
    role="tab"
    id="tab-{{ $id }}"
    data-hs-tab="#panel-{{ $id }}"
    aria-controls="panel-{{ $id }}"
    aria-selected="{{ $active ? 'true' : 'false' }}"
    @disabled ($disabled)
    {{
$attributes->class([
        'hs-tab-active:border-b-transparent hs-tab-active:bg-card hs-tab-active:text-primary dark:hs-tab-active:bg-default-950',
        'active' => $active,
        'inline-flex items-center justify-center gap-2 rounded-t-xl border border-b px-4 py-2.5 text-sm font-medium transition-all',
        'border-default-300 text-default-500 hover:text-primary disabled:pointer-events-none disabled:opacity-50 dark:border-default-700 dark:text-default-400',
        'w-full md:flex-1' => $justified,
        'min-w-max' => ! $justified,
    ])
}}
>
    @if ($icon)
        <i class="iconify {{ $icon }} shrink-0 text-base"></i>
    @endif

    <span>{{ $slot }}</span>
</button>
