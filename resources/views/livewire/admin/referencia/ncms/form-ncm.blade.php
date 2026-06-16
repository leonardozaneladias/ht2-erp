<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo NCM' : 'Editar NCM'"
        subtitle="Preencha os dados do NCM."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'NCMs', 'url' => route('admin.referencia.ncms.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.ncms.index')"
                variant="default"
                appearance="outline"
                wire:navigate
            >
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card>
        <div class="grid grid-cols-1 gap-4">
            <x-shared.input name="codigo" label="Código" wire:model="codigo" required maxlength="8" />
            <x-shared.textarea name="descricao" label="Descrição" wire:model="descricao" :rows="6" required />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.referencia.ncms.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
