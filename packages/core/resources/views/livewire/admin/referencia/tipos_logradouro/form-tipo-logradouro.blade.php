<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo tipo de logradouro' : 'Editar tipo de logradouro'"
        subtitle="Preencha os dados do tipo de logradouro."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Tipos de logradouro', 'url' => route('admin.referencia.tipos_logradouro.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.tipos_logradouro.index')"
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
                <x-shared.input name="nome" label="Nome" wire:model="nome" required maxlength="60" />
                <x-shared.input name="codigo" label="Código" wire:model="codigo" maxlength="10" />
                <x-shared.input name="abrev" label="Abreviação" wire:model="abrev" maxlength="15" />
                <x-shared.toggle name="ativo" label="Ativo" wire:model="ativo" stacked />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.tipos_logradouro.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
