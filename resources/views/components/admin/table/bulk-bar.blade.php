@props ([
    'count' => 0,
    'label' => 'selecionado(s)',
])

{{-- Wrapper SEMPRE presente no DOM: vira a barra visível quando há seleção e fica sr-only
     (oculto, fora do fluxo — sem gap) quando count=0. Mantê-lo sempre renderizado garante
     que a live region role="status" seja registrada pelo leitor ao montar a tabela,
     anunciando o aparecimento da seleção em massa de forma confiável quando o texto entra
     (count>0), inclusive em leitores que não detectam regiões recém-inseridas (WCAG 4.1.3 -
     Status Messages). Mesmo padrão do x-admin.unsaved-bar. --}}
<div
    @class ([
        'border-primary/25 bg-primary/8 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3' => $count > 0,
        'sr-only' => $count === 0,
    ])
>
    {{-- role="status" envolve só a mensagem (contagem + label), não o slot de ações. --}}
    <span class="text-body-color inline-flex items-center gap-2 text-sm font-medium" role="status">
        @if ($count > 0)
            <span
                class="bg-primary inline-flex size-6 items-center justify-center rounded-full text-xs font-bold text-white"
            >
                {{ $count }}
            </span>
            {{ $label }}
        @endif
    </span>

    @if ($count > 0)
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
