<div class="space-y-6">
    <x-admin.page-header title="Bancos" subtitle="Participantes do SPB — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.bancos.create')" icon="tabler--plus" wire:navigate>
                    Novo banco
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.banco-table />
</div>
