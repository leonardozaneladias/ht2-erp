<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo cargo' : 'Editar cargo'"
        subtitle="Preencha os dados do cargo (CBO)."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Cargos', 'url' => route('admin.referencia.cargos.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.cargos.index')"
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
            <x-shared.input name="codigo_cbo" label="Código CBO" wire:model="codigo_cbo" required maxlength="6" />
            <x-shared.input name="descricao" label="Descrição" wire:model="descricao" required />
            <x-shared.toggle name="ativo" label="Ativo" wire:model="ativo" stacked />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.referencia.cargos.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
