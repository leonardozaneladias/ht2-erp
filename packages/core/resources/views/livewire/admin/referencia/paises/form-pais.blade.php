<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo país' : 'Editar país'"
        subtitle="Preencha os dados do país."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Países', 'url' => route('admin.referencia.paises.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.paises.index')"
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
                    name="codigo_iso2"
                    label="Código ISO2"
                    wire:model="codigo_iso2"
                    required
                    maxlength="2"
                />
                <x-shared.input name="codigo_iso3" label="Código ISO3" wire:model="codigo_iso3" maxlength="3" />
                <x-shared.input
                    name="codigo_numerico"
                    label="Código numérico"
                    wire:model="codigo_numerico"
                    maxlength="3"
                />
                <x-shared.input name="nome" label="Nome" wire:model="nome" required />
                <x-shared.toggle name="ativo" label="Ativo" wire:model="ativo" stacked />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.paises.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
