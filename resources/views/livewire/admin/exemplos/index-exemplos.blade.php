<div class="space-y-6">
    <x-admin.page-header title="Exemplos" subtitle="Cadastro de Exemplos.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.exemplos.create')" icon="tabler--plus" wire:navigate>
                    Novo registro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.exemplos.exemplo-table />
</div>
