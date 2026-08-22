@props ([
    'title',
    // Colunas em telas médias (o mobile é sempre 2).
    'cols' => 3,
])

{{--
    Seção da ficha "Ver" — título + grade de x-shared.field-display.

    O bloco `<section> + <h4 class="…"> + <div class="grid …">` estava copiado 39 vezes em
    27 fichas. Mudar o espaçamento ou a tipografia do título exigia 39 edições — e bastava
    esquecer uma para a ficha daquele CRUD destoar das outras.
--}}

@php
    // Classes literais: o JIT do Tailwind não enxerga `md:grid-cols-{$cols}` interpolado.
    $colunas = match ((int) $cols) {
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        4 => 'md:grid-cols-4',
        default => 'md:grid-cols-3',
    };
@endphp

<section {{ $attributes }}>
    <h4 class="text-body-color mb-3 text-sm font-semibold">{{ $title }}</h4>

    <div class="grid grid-cols-2 gap-x-4 gap-y-4 {{ $colunas }}">{{ $slot }}</div>

    {{-- Campos que não cabem na grade (um texto longo, uma lista de tags) entram aqui, em
         largura total, sem sair da seção. --}}
    @isset ($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset
</section>
