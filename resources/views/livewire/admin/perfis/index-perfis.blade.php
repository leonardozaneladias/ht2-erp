<div class="space-y-6">
    <x-admin.page-header title="Perfis e permissões" subtitle="Defina papéis e o que cada um pode fazer no painel.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.perfis.create')" icon="tabler--plus" wire:navigate>
                    Novo perfil
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.perfis.perfis-table />
</div>
