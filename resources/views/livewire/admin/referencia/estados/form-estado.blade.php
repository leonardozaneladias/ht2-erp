<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo estado' : 'Editar estado'"
        subtitle="Preencha os dados do estado (UF)."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Estados', 'url' => route('admin.referencia.estados.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.estados.index')"
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
            <x-shared.input name="codigo_ibge" label="Código IBGE" wire:model="codigo_ibge" required maxlength="2" />
            <x-shared.input name="sigla" label="Sigla" wire:model="sigla" required maxlength="2" />
            <x-shared.input name="nome" label="Nome" wire:model="nome" required />
            <x-shared.select-search name="regiao" label="Região" wire:model="regiao" :options="$regioes" required />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.referencia.estados.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
