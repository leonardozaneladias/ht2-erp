<div class="space-y-6">
    <x-admin.page-header title="Produtos" subtitle="Cadastro de Produtos.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.produtos.create')" icon="tabler--plus" wire:navigate>
                    Novo registro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.produtos.produto-table />
</div>
