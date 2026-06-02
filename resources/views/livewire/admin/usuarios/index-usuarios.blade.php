<div class="space-y-6">
    <x-admin.page-header title="Usuários admin" subtitle="Gerencie quem tem acesso ao painel administrativo.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.usuarios.create')" icon="tabler--plus" wire:navigate>
                    Novo usuário
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.usuarios.usuarios-table />
</div>
