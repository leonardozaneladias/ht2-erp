{{-- Rodapé padrão de formulários: Cancelar + ação primária com loading. --}}
{{-- submit=true: o botão primário vira type="submit" (sem wire:click), para ser
     disparado pelo <form wire:submit="..."> ancestral — é isso que faz o Enter salvar.
     submit=false (default): mantém o disparo por wire:click (100% retrocompatível
     com as telas que ainda não envolvem o corpo em <form>). --}}
{{-- sticky=true: o rodapé gruda no fim da viewport. Em formulários longos (o cadastro de
     funcionário tem 86 campos), o "Salvar" ficava no fim da página — para salvar uma
     correção de uma linha era preciso rolar tudo. Prop aditiva: quem não passa nada
     continua com o rodapé no fluxo normal. --}}
@props ([
    'cancelHref',
    'cancelLabel' => 'Cancelar',
    'action' => 'salvar',
    'label' => 'Salvar',
    'loadingLabel' => 'Salvando...',
    'submit' => false,
    'sticky' => false,
])

<div
    {{
$attributes->class([
            'flex justify-end gap-2',
            // O fundo e a borda existem só no modo sticky: sobrepondo o conteúdo, o rodapé
            // precisa de um plano de fundo para não deixar o texto passar por baixo.
            'bg-body-bg/85 border-default-200 sticky bottom-0 z-20 -mx-4 border-t px-4 py-3 backdrop-blur-sm sm:-mx-6 sm:px-6' => $sticky,
        ])
}}
>
    <x-shared.button :href="$cancelHref" variant="default" appearance="outline" wire:navigate>
        {{ $cancelLabel }}
    </x-shared.button>
    @if ($submit)
        <x-shared.button type="submit" variant="primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="{{ $action }}">{{ $label }}</span>
            <span wire:loading wire:target="{{ $action }}">{{ $loadingLabel }}</span>
        </x-shared.button>
    @else
        <x-shared.button variant="primary" wire:click="{{ $action }}" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="{{ $action }}">{{ $label }}</span>
            <span wire:loading wire:target="{{ $action }}">{{ $loadingLabel }}</span>
        </x-shared.button>
    @endif
</div>
