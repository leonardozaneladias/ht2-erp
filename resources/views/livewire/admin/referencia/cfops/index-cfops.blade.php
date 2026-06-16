<div class="space-y-6">
    <x-admin.page-header title="CFOPs" subtitle="Código Fiscal de Operações e Prestações — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.cfops.create')" icon="tabler--plus" wire:navigate>
                    Novo CFOP
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.cfop-table />
</div>
