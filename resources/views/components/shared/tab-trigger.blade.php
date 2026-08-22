@props ([
    'id',
    'active' => false,
    'disabled' => false,
    'icon' => null,
    'justified' => false,
    // Indica erro de validação em campo desta aba (visível mesmo com a aba inativa).
    'hasError' => false,
    // false = troca via servidor (Livewire): omite data-hs-tab; o estado ativo vem
    // de :active (não das variantes hs-tab-active do Preline). Combine com wire:click.
    'preline' => true,
    // aria-controls: associa a aba ao painel (WAI-ARIA Tabs Pattern). Valores:
    //   null   → legado: no modo Preline aponta para "panel-{id}"; no server-driven
    //            é OMITIDO (use quando não há x-shared.tab-panel no DOM, ex.: conteúdo
    //            trocado por condicional no servidor — evita referência pendente).
    //   true   → força aria-controls="panel-{id}" (server-driven COM x-shared.tab-panel).
    //   string → aria-controls="{controls}" (id de painel customizado).
    //   false  → nunca emite aria-controls.
    'controls' => null,
])

@php
    $controlsId = match (true) {
        $controls === false => null,
        is_string($controls) && $controls !== '' => $controls,
        $controls === true => "panel-{$id}",
        $preline => "panel-{$id}",
        default => null,
    };
@endphp

<button
    type="button"
    role="tab"
    id="tab-{{ $id }}"
    @if ($preline)
        data-hs-tab="#panel-{{ $id }}"
    @endif
    @if ($controlsId)
        aria-controls="{{ $controlsId }}"
    @endif
    aria-selected="{{ $active ? 'true' : 'false' }}"
    @disabled ($disabled)
    {{
$attributes->class([
        // `-mb-px` faz o gatilho descer 1px e sobrepor a linha-base da tab-nav:
        // a aba ativa (border-b-transparent + bg-card) "abre" no x-shared.tab-body
        // logo abaixo, sem emenda — o visual conectado de fichário.
        'relative -mb-px inline-flex cursor-pointer items-center justify-center gap-2 rounded-t border border-transparent px-4 py-2.5 text-sm font-medium transition-colors duration-200',
        'text-default-500 hover:text-primary hover:border-default-200 disabled:pointer-events-none disabled:opacity-50 dark:text-default-400 dark:hover:border-default-700',
        'hs-tab-active:border-default-300 hs-tab-active:border-b-transparent hs-tab-active:bg-card hs-tab-active:text-primary dark:hs-tab-active:border-default-700 dark:hs-tab-active:bg-default-950' => $preline,
        // Server-driven (Livewire): aplica o estado ativo direto via :active.
        'border-default-300 border-b-transparent bg-card text-primary dark:border-default-700 dark:bg-default-950' => ! $preline && $active,
        'active' => $active && $preline,
        'w-full md:flex-1' => $justified,
        'min-w-max' => ! $justified,
    ])
}}
>
    @if ($icon)
        <i class="iconify {{ $icon }} shrink-0 text-base" aria-hidden="true"></i>
    @endif

    <span>{{ $slot }}</span>

    @if ($hasError)
        <span class="bg-danger size-2 shrink-0 rounded-full" aria-hidden="true"></span>
        <span class="sr-only">(há erros nesta aba)</span>
    @endif
</button>
