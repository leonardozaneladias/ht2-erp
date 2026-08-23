@props ([
    'label',
    // Rótulo acessível do que está oculto ("salário base") — vai no aria-label do
    // botão. Default: o próprio label em minúsculas.
    'ariaTarget' => null,
])

{{--
    Valor sensível atrás de um toggle Mostrar/Ocultar (•••••• ↔ valor). O slot é o
    valor JÁ formatado — o componente não formata nada (dinheiro continua vindo de
    Money::formatado()).

    Como o x-shared.toggle/field-display, NÃO mescla `class` na raiz: para col-span
    ou margens, envolva num wrapper. Nasceu na D13 do doc 31 (dívida da C17): as duas
    cópias inline (ficha e MeusDados) divergiam em tokens e não tinham aria-pressed.
--}}

@php ($alvoA11y = $ariaTarget ?? mb_strtolower($label))

<div x-data="{ ver: false }">
    <p class="text-default-500 text-xs font-medium tracking-wide uppercase">{{ $label }}</p>
    <div class="mt-1 flex items-center gap-3">
        <span class="text-body-color text-sm">
            <span x-show="!ver" aria-hidden="true">••••••</span>
            <span x-show="ver" x-cloak>{{ $slot }}</span>
        </span>
        <button
            type="button"
            @click="ver = !ver;"
            aria-pressed="false"
            :aria-pressed="ver ? 'true' : 'false'"
            aria-label="Mostrar {{ $alvoA11y }}"
            :aria-label="ver ? 'Ocultar {{ $alvoA11y }}' : 'Mostrar {{ $alvoA11y }}'"
            class="text-primary text-xs font-medium hover:underline"
        >
            <span x-show="!ver">Mostrar</span>
            <span x-show="ver" x-cloak>Ocultar</span>
        </button>
    </div>
</div>
