@props ([
    // Numero de linhas de texto. 0 = um bloco unico (defina tamanho via class).
    'lines' => 0,
    // Formato circular (avatar/ícone). Ignora `lines`.
    'circle' => false,
])

{{--
    Placeholder de carregamento (skeleton). Primitivo reutilizavel para estados
    de loading sofisticados — usar dentro de wire:loading ou enquanto dados
    assincronos chegam. Respeita prefers-reduced-motion (sem pulsar).

    IMPORTANTE: nao escrever exemplos com a tag literal "x-shared.skeleton"
    (com angle bracket) aqui dentro — o Blade compila componentes ANTES de
    remover comentarios, e a auto-referencia causaria recursao infinita.
    Veja exemplos de uso no showcase: /admin/dev/components/skeleton

    Props: lines (n de linhas de texto; 0 = bloco unico) · circle (avatar).
--}}

@php
    $base = 'animate-pulse bg-default-200 motion-reduce:animate-none';
@endphp

@if ($circle)
    <div {{ $attributes->class([$base, 'rounded-full']) }} aria-hidden="true"></div>
@elseif ($lines > 0)
    <div {{ $attributes->class('flex w-full flex-col gap-2.5') }} aria-hidden="true">
        @for ($i = 0; $i < (int) $lines; $i++)
            <div
                @class ([$base, 'h-3 rounded', 'w-2/3' => $i === (int) $lines - 1, 'w-full' => $i !== (int) $lines - 1])
            ></div>
        @endfor
    </div>
@else
    <div {{ $attributes->class([$base, 'rounded']) }} aria-hidden="true"></div>
@endif
