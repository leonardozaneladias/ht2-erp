<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo registro' : 'Editar registro'"
        subtitle="Preencha os dados de Funcionario."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Funcionarios', 'url' => route('admin.rh.funcionarios.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button
                :href="route('admin.rh.funcionarios.index')"
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
            <x-shared.cpf-input name="cpf" wire:model="cpf" required />
            <x-shared.select-search
                name="cargo"
                label="Cargo"
                placeholder="Selecione o cargo..."
                :options="$this->cargosDisponiveis"
                wire:model="cargo"
                required
            />
            <x-shared.money-input name="salario" label="Salario" wire:model="salario" required />
            <x-shared.date-picker name="admissao" label="Admissao" wire:model="admissao" required />
            <x-shared.select-search
                name="status"
                label="Status"
                wire:model="status"
                :options="\HT2ML\Rh\Enums\StatusFuncionario::options()"
                required
            />
        </div>
    </x-shared.card>

    <x-admin.form-footer
        :cancel-href="route('admin.rh.funcionarios.index')"
        :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
    />
</div>
