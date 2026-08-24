<x-shared.card title="Componente Livewire — Exemplo">
    <p class="text-default-500 mb-4">Este é um componente Livewire simples demonstrando reatividade sem JavaScript. Use como ponto de partida para componentes do admin.</p>

    <div class="flex items-center gap-3">
        <x-shared.button variant="default" appearance="outline" size="sm" icon="tabler--minus" wire:click="decrement">
            Diminuir
        </x-shared.button>

        <span class="px-3 text-2xl font-bold">{{ $count }}</span>

        <x-shared.button variant="primary" size="sm" icon="tabler--plus" wire:click="increment">
            Aumentar
        </x-shared.button>

        <x-shared.button variant="danger" appearance="outline" size="sm" class="ms-2" wire:click="resetar">
            Resetar
        </x-shared.button>
    </div>
</x-shared.card>
