<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo banco' : 'Editar banco'"
        subtitle="Preencha os dados do banco (SPB)."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Bancos', 'url' => route('admin.referencia.bancos.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.bancos.index')"
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
                <x-shared.input name="ispb" label="ISPB" wire:model="ispb" required maxlength="8" />
                <x-shared.input name="codigo_compe" label="Código COMPE" wire:model="codigo_compe" maxlength="3" />
                <x-shared.input name="nome" label="Nome" wire:model="nome" required />
                <x-shared.input name="nome_completo" label="Nome completo" wire:model="nome_completo" />
                <x-shared.toggle name="ativo" label="Ativo" wire:model="ativo" stacked />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.bancos.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
