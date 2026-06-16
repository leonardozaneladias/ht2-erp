<div class="space-y-6">
    <x-admin.page-header title="CNAEs" subtitle="Classificação Nacional de Atividades Econômicas — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.cnaes.create')" icon="tabler--plus" wire:navigate>
                    Novo CNAE
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.cnae-table />
</div>
