<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo CFOP' : 'Editar CFOP'"
        subtitle="Preencha os dados do CFOP."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'CFOPs', 'url' => route('admin.referencia.cfops.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.cfops.index')"
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
                <x-shared.input name="codigo" label="Código" wire:model="codigo" required maxlength="4" />
                <x-shared.select-search name="tipo" label="Tipo" wire:model="tipo" :options="$tipos" required />
                <x-shared.input name="descricao" label="Descrição" wire:model="descricao" required />
                <x-shared.input name="aplicacao" label="Aplicação" wire:model="aplicacao" />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.cfops.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
