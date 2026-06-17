<div class="space-y-6">
    <x-admin.page-header title="Funcionarios" subtitle="Cadastro de Funcionarios.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.rh.funcionarios.create')" icon="tabler--plus" wire:navigate>
                    Novo registro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:rh.funcionarios.funcionario-table />
</div>
