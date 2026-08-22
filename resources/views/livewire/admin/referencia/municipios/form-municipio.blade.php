<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo município' : 'Editar município'"
        subtitle="Preencha os dados do município (IBGE)."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Municípios', 'url' => route('admin.referencia.municipios.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.municipios.index')"
                variant="default"
                appearance="outline"
                wire:navigate
            >
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- wire:submit + form-footer submit: salva com Enter (e mantém o clique no botão). --}}
    <form wire:submit="salvar" class="space-y-6">
        <x-shared.card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-shared.input
                    name="codigo_ibge"
                    label="Código IBGE"
                    wire:model="codigo_ibge"
                    required
                    maxlength="7"
                />
                <x-shared.input name="nome" label="Nome" wire:model="nome" required />
                <x-shared.select-search
                    name="estado_id"
                    label="Estado (UF)"
                    wire:model="estado_id"
                    :options="$estados"
                    required
                />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.municipios.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
