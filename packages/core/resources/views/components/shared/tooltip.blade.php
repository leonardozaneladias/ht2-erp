@props ([
    'content',
    'placement' => 'top',
    // id do balão (role="tooltip"); gerado quando ausente. O gatilho recebe
    // aria-describedby apontando para ele, associando dica↔controle (WAI-ARIA
    // Tooltip Pattern / WCAG 4.1.2). Informe um id estável quando o gatilho do
    // slot for um elemento focável próprio (botão/link) e você quiser repetir
    // o mesmo aria-describedby="{id}" diretamente nele, garantindo o anúncio.
    'id' => null,
])

@php
    $placement = in_array($placement, ['top', 'bottom', 'left', 'right'], true) ? $placement : 'top';
    $tooltipId = $id ?: 'tooltip-' . \Illuminate\Support\Str::random(6);
@endphp

<span {{ $attributes->class(['hs-tooltip relative inline-flex', "[--placement:{$placement}]"]) }}>
    <span class="hs-tooltip-toggle inline-flex" aria-describedby="{{ $tooltipId }}">
        {{ $slot }}

        <span
            id="{{ $tooltipId }}"
            class="hs-tooltip-content hs-tooltip-shown:visible hs-tooltip-shown:opacity-100 bg-dark invisible absolute z-30 inline-block rounded-lg px-3 py-2 text-xs font-medium whitespace-nowrap text-white opacity-0 shadow-2xs transition-opacity"
            role="tooltip"
        >
            {{ $content }}
        </span>
    </span>
</span>
