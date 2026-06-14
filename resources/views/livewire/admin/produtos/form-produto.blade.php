<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo registro' : 'Editar registro'"
        subtitle="Preencha os dados de Produto."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Produtos', 'url' => route('admin.produtos.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button :href="route('admin.produtos.index')" variant="default" appearance="outline" wire:navigate>
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card title="Dados">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="nome" label="Nome" wire:model="nome" required />
            <x-shared.input name="sku" label="Sku" wire:model="sku" required />
            <x-shared.input
                type="number"
                min="0"
                step="1"
                name="preco"
                label="Preco (centavos)"
                wire:model="preco"
                required
            />
            <x-shared.textarea name="descricao" label="Descricao" wire:model="descricao" />
            <x-shared.select
                name="status"
                label="Status"
                wire:model="status"
                :options="\App\Enums\StatusProduto::options()"
                required
            />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.produtos.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
