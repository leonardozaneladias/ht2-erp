<x-shared.card title="Componente Livewire — Exemplo">
    <p class="text-muted mb-4">Este é um componente Livewire simples demonstrando reatividade sem JavaScript. Use como ponto de partida para componentes do admin.</p>

    <div class="d-flex align-items-center gap-3">
        <button wire:click="decrement" class="btn btn-outline-secondary btn-sm">
            <iconify-icon icon="tabler--minus" class="me-1"></iconify-icon>
            Diminuir
        </button>

        <span class="fs-3 fw-bold px-3">{{ $count }}</span>

        <button wire:click="increment" class="btn btn-primary btn-sm">
            <iconify-icon icon="tabler--plus" class="me-1"></iconify-icon>
            Aumentar
        </button>

        <button wire:click="resetar" class="btn btn-outline-danger btn-sm ms-2">Resetar</button>
    </div>
</x-shared.card>
