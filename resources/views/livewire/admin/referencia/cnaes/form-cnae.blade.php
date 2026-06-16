<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo CNAE' : 'Editar CNAE'"
        subtitle="Preencha os dados do CNAE."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'CNAEs', 'url' => route('admin.referencia.cnaes.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.cnaes.index')"
                variant="default"
                appearance="outline"
                wire:navigate
            >
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="codigo" label="Código" wire:model="codigo" required maxlength="7" />
            <x-shared.input name="descricao" label="Descrição" wire:model="descricao" required maxlength="255" />
            <x-shared.input name="secao" label="Seção" wire:model="secao" maxlength="1" />
            <x-shared.input name="divisao" label="Divisão" wire:model="divisao" maxlength="2" />
            <x-shared.input name="grupo" label="Grupo" wire:model="grupo" maxlength="3" />
            <x-shared.input name="classe" label="Classe" wire:model="classe" maxlength="5" />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.referencia.cnaes.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
