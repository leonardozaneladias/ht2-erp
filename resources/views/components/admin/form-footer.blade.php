{{-- Rodapé padrão de formulários: Cancelar + ação primária com loading. --}}
@props ([
    'cancelHref',
    'cancelLabel' => 'Cancelar',
    'action' => 'salvar',
    'label' => 'Salvar',
    'loadingLabel' => 'Salvando...',
])

<div {{ $attributes->class(['flex justify-end gap-2']) }}>
    <x-shared.button :href="$cancelHref" variant="default" appearance="outline" wire:navigate>
        {{ $cancelLabel }}
    </x-shared.button>
    <x-shared.button variant="primary" wire:click="{{ $action }}" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="{{ $action }}">{{ $label }}</span>
        <span wire:loading wire:target="{{ $action }}">{{ $loadingLabel }}</span>
    </x-shared.button>
</div>
