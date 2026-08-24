{{-- Linha de item do menu (raiz da seção ou filho de grupo).
     Espera: $item (entry da estrutura) e $handle (classe do grip do nível). --}}
<div
    wire:key="menu-item-{{ $item['key'] }}"
    data-id="{{ $item['key'] }}"
    data-tipo="item"
    data-busca="{{ mb_strtolower($item['label'] . ' ' . $item['labelPadrao'] . ' ' . $item['route']) }}"
    x-show="casaOuPaiCasa($el)"
    @class ([
        'border-default-200 flex items-center gap-2.5 border-b px-2 py-2 last:border-b-0',
        'opacity-60' => ! $item['ativo'],
    ])
>
    <i
        class="iconify tabler--grip-vertical {{ $handle }} text-default-400 shrink-0 cursor-grab text-lg"
        aria-hidden="true"
        x-bind:class="busca.trim() !== '' ? 'pointer-events-none opacity-30' : ''"
    ></i>

    <span class="bg-light text-default-600 flex size-8 shrink-0 items-center justify-center rounded-lg">
        <i class="iconify {{ $item['icon'] }} text-base" aria-hidden="true"></i>
    </span>

    <div class="min-w-0 grow">
        <p class="text-body-color truncate text-sm font-medium">
            {{ $item['label'] }}

            @if ($item['personalizado'] && $item['label'] !== $item['labelPadrao'])
                <span class="text-default-400 text-xs font-normal">(padrão: {{ $item['labelPadrao'] }})</span>
            @endif
        </p>
        <p class="text-default-400 truncate text-xs">{{ $item['route'] }}</p>
    </div>

    <div class="flex shrink-0 items-center gap-1.5">
        @unless ($item['ativo'])
            <x-shared.badge variant="danger" size="sm" icon="tabler--eye-off">Inativo</x-shared.badge>
        @endunless

        @if ($item['personalizado'])
            <span
                class="text-info flex size-6 items-center justify-center"
                title="Personalizado (difere do padrão do módulo)"
            >
                <i class="iconify tabler--pencil-star text-sm" aria-hidden="true"></i>
                <span class="sr-only">Personalizado (difere do padrão do módulo)</span>
            </span>
        @endif

        @if (($item['permission'] ?? null) !== null)
            <span
                class="text-default-400 flex size-6 items-center justify-center"
                title="Visível para quem tem {{ $item['permission'] }}"
            >
                <i class="iconify tabler--lock text-sm" aria-hidden="true"></i>
                <span class="sr-only">Visível para quem tem {{ $item['permission'] }}</span>
            </span>
        @else
            <span class="text-success flex size-6 items-center justify-center" title="Visível para todos os perfis">
                <i class="iconify tabler--world text-sm" aria-hidden="true"></i>
                <span class="sr-only">Visível para todos os perfis</span>
            </span>
        @endif

        <x-shared.button
            variant="default"
            appearance="ghost"
            size="sm"
            :icon="$item['ativo'] ? 'tabler--eye' : 'tabler--eye-off'"
            icon-only
            aria-label="{{ $item['ativo'] ? 'Desativar' : 'Reativar' }} item {{ $item['label'] }}"
            wire:click="alternarAtivo('{{ $item['key'] }}')"
        />

        <x-shared.button
            variant="default"
            appearance="ghost"
            size="sm"
            icon="tabler--pencil"
            icon-only
            aria-label="Editar item {{ $item['label'] }}"
            wire:click="editar('item', '{{ $item['key'] }}')"
        />
    </div>
</div>
