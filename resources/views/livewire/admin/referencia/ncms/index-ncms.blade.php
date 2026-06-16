<div class="space-y-6">
    <x-admin.page-header title="NCMs" subtitle="Nomenclatura Comum do Mercosul — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.ncms.create')" icon="tabler--plus" wire:navigate>
                    Novo NCM
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.ncm-table />
</div>
