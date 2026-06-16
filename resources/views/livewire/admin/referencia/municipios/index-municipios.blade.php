<div class="space-y-6">
    <x-admin.page-header title="Municípios" subtitle="Municípios IBGE — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.municipios.create')" icon="tabler--plus" wire:navigate>
                    Novo município
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.municipio-table />
</div>
