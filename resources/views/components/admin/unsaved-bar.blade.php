@props ([
    'count' => 0,
    'save' => 'salvar',
    'discard' => 'descartar',
])

{{-- Wrapper SEMPRE presente no DOM: vira a barra visível quando há alterações e fica
     sr-only (oculto, fora do fluxo — sem gap) quando count=0. Mantê-lo sempre renderizado
     garante que a live region role="status" seja registrada pelo leitor ao montar a tela,
     anunciando "N alterações não salvas" de forma confiável quando o texto entra (count>0),
     inclusive em leitores que não detectam regiões recém-inseridas (WCAG 4.1.3 - Status
     Messages). --}}
<div
    @class ([
        'border-default-200 bg-default-50 flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3' => $count > 0,
        'sr-only' => $count === 0,
    ])
>
    {{-- role="status" envolve só a mensagem (não os botões), para anunciar "N alterações
         não salvas" sem ler os controles. --}}
    <span class="text-sm" role="status">
        @if ($count > 0)
            <span class="iconify tabler--alert-circle text-warning me-1 size-4 align-middle" aria-hidden="true"></span>
            {{ $count }} {{ $count === 1 ? 'alteração não salva' : 'alterações não salvas' }}
        @endif
    </span>
    @if ($count > 0)
        <div class="flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="{{ $discard }}">
                Descartar
            </button>
            <button
                type="button"
                class="btn btn-primary btn-sm"
                wire:click="{{ $save }}"
                wire:loading.attr="disabled"
                wire:target="{{ $save }}"
            >
                <span wire:loading.remove wire:target="{{ $save }}">Salvar</span>
                <span wire:loading wire:target="{{ $save }}">Salvando...</span>
            </button>
        </div>
    @endif
</div>
