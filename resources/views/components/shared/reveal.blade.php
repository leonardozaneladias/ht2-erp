@props ([
    // Atraso da entrada em ms, para stagger em cascata (0 = imediato).
    'delay' => 0,
])

{{-- Entrada padrão de blocos que trocam via @if (etapas de wizard, resultados).
     A animação CSS roda só quando o nó ENTRA no DOM; o morph do Livewire preserva
     nós existentes, então re-renders (ex.: wire:poll) não a repetem. --}}
<div
    {{ $attributes->class(['animate-fade-up motion-reduce:animate-none']) }}
    @if ((int) $delay > 0) style="animation-delay: {{ (int) $delay }}ms" @endif
>
    {{ $slot }}
</div>
