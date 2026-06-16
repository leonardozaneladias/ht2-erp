<div class="space-y-6">
    <x-admin.page-header title="Países" subtitle="Países — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.paises.create')" icon="tabler--plus" wire:navigate>
                    Novo país
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.pais-table />
</div>
