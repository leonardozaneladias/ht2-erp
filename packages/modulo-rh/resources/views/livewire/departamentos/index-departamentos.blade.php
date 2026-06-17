<div class="space-y-6">
    <x-admin.page-header title="Departamentos" subtitle="Cadastro de Departamentos.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.rh.departamentos.create')" icon="tabler--plus" wire:navigate>
                    Novo registro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:rh.departamentos.departamento-table />
</div>
