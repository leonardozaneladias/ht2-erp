<div class="space-y-6">
    <x-admin.page-header title="Cargos" subtitle="Cargos (CBO) — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.cargos.create')" icon="tabler--plus" wire:navigate>
                    Novo cargo
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.cargo-table />
</div>
