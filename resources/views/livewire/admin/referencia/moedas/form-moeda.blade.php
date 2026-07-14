<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Nova moeda' : 'Editar moeda'"
        subtitle="Preencha os dados da moeda (ISO 4217)."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Moedas', 'url' => route('admin.referencia.moedas.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.referencia.moedas.index')"
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
                <x-shared.input name="codigo_iso" label="Código ISO" wire:model="codigo_iso" required maxlength="3" />
                <x-shared.input name="numerico" label="Numérico" wire:model="numerico" maxlength="3" />
                <x-shared.input name="nome" label="Nome" wire:model="nome" required />
                <x-shared.input name="simbolo" label="Símbolo" wire:model="simbolo" maxlength="10" />
                <x-shared.input
                    type="number"
                    min="0"
                    max="10"
                    step="1"
                    name="casas_decimais"
                    label="Casas decimais"
                    wire:model="casas_decimais"
                    required
                />
                <x-shared.toggle name="ativo" label="Ativo" wire:model="ativo" stacked />
            </div>
        </x-shared.card>

        <x-admin.form-footer
            :cancel-href="route('admin.referencia.moedas.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
