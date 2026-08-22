<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo registro' : 'Editar registro'"
        subtitle="Preencha os dados de Departamento."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Departamentos', 'url' => route('admin.rh.departamentos.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.rh.departamentos.index')"
                variant="default"
                appearance="outline"
                wire:navigate
            >
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card title="Dados">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="nome" label="Nome" wire:model="nome" required />
            <x-shared.input name="sigla" label="Sigla" wire:model="sigla" required />
            <x-shared.select-search
                name="status"
                label="Status"
                wire:model="status"
                :options="\HT2ML\Rh\Enums\StatusDepartamento::options()"
                required
            />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.rh.departamentos.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
