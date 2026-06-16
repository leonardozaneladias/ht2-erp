<div class="space-y-6">
    <x-admin.page-header title="Moedas" subtitle="Moedas (ISO 4217) — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.moedas.create')" icon="tabler--plus" wire:navigate>
                    Nova moeda
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.moeda-table />
</div>
