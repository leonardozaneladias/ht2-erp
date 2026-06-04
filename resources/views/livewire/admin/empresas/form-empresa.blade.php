<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Nova empresa' : 'Editar empresa'"
        subtitle="Dados cadastrais e identidade visual aplicada quando a empresa está ativa."
    >
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.empresas.index') }}" wire:navigate>Cancelar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-shared.card title="Identificação">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="nome" label="Nome" wire:model="nome" required />
            <x-shared.input name="razao_social" label="Razão social" wire:model="razao_social" />
            <x-shared.input name="cnpj" label="CNPJ" placeholder="00.000.000/0000-00" wire:model="cnpj" />
            <x-shared.input name="inscricao_estadual" label="Inscrição estadual" wire:model="inscricao_estadual" />
            <x-shared.input type="email" name="email" label="E-mail" wire:model="email" />
            <x-shared.input name="telefone" label="Telefone" wire:model="telefone" />
            <x-shared.input name="site_url" label="Site" placeholder="https://" wire:model="site_url" />
            <x-shared.toggle name="ativo" label="Empresa ativa" wire:model="ativo" />
        </div>
    </x-shared.card>

    <x-shared.card
        title="Identidade visual"
        subtitle="Cores no formato #RRGGBB. Em branco, herdam o tema da instância."
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-shared.input name="cor_primaria" label="Cor primária" placeholder="#RRGGBB" wire:model="cor_primaria" />
            <x-shared.input
                name="cor_secundaria"
                label="Cor secundária"
                placeholder="#RRGGBB"
                wire:model="cor_secundaria"
            />
            <x-shared.input name="cor_sucesso" label="Cor de sucesso" placeholder="#RRGGBB" wire:model="cor_sucesso" />
            <x-shared.input name="cor_warning" label="Cor de alerta" placeholder="#RRGGBB" wire:model="cor_warning" />
            <x-shared.input name="cor_perigo" label="Cor de perigo" placeholder="#RRGGBB" wire:model="cor_perigo" />
            <x-shared.input name="cor_info" label="Cor de informação" placeholder="#RRGGBB" wire:model="cor_info" />
        </div>
    </x-shared.card>

    <div class="flex justify-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.empresas.index') }}" wire:navigate>Cancelar</a>
        <button type="button" class="btn btn-primary" wire:click="salvar" wire:loading.attr="disabled">
            <span
                wire:loading.remove
                wire:target="salvar"
                >{{ $modo === 'criar' ? 'Criar empresa' : 'Salvar alterações' }}</span
            >
            <span wire:loading wire:target="salvar">Salvando...</span>
        </button>
    </div>
</div>
