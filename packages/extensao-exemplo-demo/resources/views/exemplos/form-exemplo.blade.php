<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo registro' : 'Editar registro'"
        subtitle="Preencha os dados de Exemplo."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Exemplos', 'url' => route('admin.exemplos.index')],
            ['label' => $modo === 'criar' ? 'Novo' : 'Editar', 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.button :href="route('admin.exemplos.index')" variant="default" appearance="outline" wire:navigate>
                Cancelar
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- wire:submit + form-footer submit: salva com Enter (e mantém o clique no botão). --}}
    <form wire:submit="salvar" class="space-y-6">
        <x-shared.tab-nav>
            <x-shared.tab-trigger
                id="aba-identificacao"
                active
                :has-error="$errors->hasAny(['nome', 'slug', 'site', 'descricao', 'filial_id'])"
            >
                Identificação</x-shared.tab-trigger
            >
            <x-shared.tab-trigger
                id="aba-contato"
                :has-error="$errors->hasAny(['email', 'telefone', 'cep', 'cnpj', 'cpf'])"
            >
                Contato</x-shared.tab-trigger
            >
            <x-shared.tab-trigger
                id="aba-comercial"
                :has-error="$errors->hasAny(['preco', 'custo', 'quantidade', 'cor', 'categoria', 'tags'])"
            >
                Comercial</x-shared.tab-trigger
            >
            <x-shared.tab-trigger
                id="aba-datas-e-status"
                :has-error="$errors->hasAny(['destaque', 'data_inicio', 'publicado_em', 'status'])"
            >
                Datas e status</x-shared.tab-trigger
            >
        </x-shared.tab-nav>

        <x-shared.tab-body>
            <x-shared.tab-panel id="aba-identificacao" active>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-shared.input name="nome" label="Nome" wire:model="nome" required />
                    <x-shared.input name="slug" label="Slug" wire:model="slug" required />
                    @if (! empty($filiais))
                        <x-shared.select-search
                            name="filial_id"
                            label="Filial"
                            wire:model="filial_id"
                            :options="$filiais"
                            placeholder="Sem filial"
                        />
                    @endif
                    <x-shared.input type="url" name="site" label="Site" wire:model="site" placeholder="https://" />
                    <x-shared.rich-editor name="descricao" label="Descrição" wire:model="descricao" />
                </div>
            </x-shared.tab-panel>

            <x-shared.tab-panel id="aba-contato">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-shared.input type="email" name="email" label="E-mail" wire:model="email" required />
                    <x-shared.phone-input name="telefone" wire:model="telefone" />
                    <x-shared.cep-input name="cep" wire:model="cep" />
                    <x-shared.cnpj-input name="cnpj" wire:model="cnpj" />
                    <x-shared.cpf-input name="cpf" wire:model="cpf" />
                </div>
            </x-shared.tab-panel>

            <x-shared.tab-panel id="aba-comercial">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-shared.input
                        type="number"
                        min="0"
                        step="1"
                        name="preco"
                        label="Preço (centavos)"
                        wire:model="preco"
                        required
                    />
                    <x-shared.input type="number" step="0.01" name="custo" label="Custo" wire:model="custo" required />
                    <x-shared.input
                        type="number"
                        name="quantidade"
                        label="Quantidade"
                        wire:model="quantidade"
                        required
                    />
                    <x-shared.color-picker name="cor" label="Cor" wire:model="cor" clearable />
                    <x-shared.select-search
                        name="categoria"
                        label="Categoria"
                        wire:model="categoria"
                        :options="['servico' => 'Serviço', 'produto' => 'Produto', 'assinatura' => 'Assinatura']"
                        required
                    />
                    <x-shared.select-search
                        name="tags"
                        label="Tags"
                        wire:model="tags"
                        :options="['vip' => 'Vip', 'novo' => 'Novo', 'promo' => 'Promo']"
                        multiple
                    />
                </div>
            </x-shared.tab-panel>

            <x-shared.tab-panel id="aba-datas-e-status">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-shared.toggle name="destaque" label="Destaque" wire:model="destaque" stacked />
                    <x-shared.date-picker name="data_inicio" label="Data início" wire:model="data_inicio" required />
                    <x-shared.date-picker name="publicado_em" label="Publicado em" wire:model="publicado_em" />
                    <x-shared.select-search
                        name="status"
                        label="Status"
                        wire:model="status"
                        :options="\HT2ML\ExemploDemo\Enums\StatusExemplo::options()"
                        required
                    />
                </div>
            </x-shared.tab-panel>
        </x-shared.tab-body>

        <x-admin.form-footer
            :cancel-href="route('admin.exemplos.index')"
            :label="$modo === 'criar' ? 'Criar' : 'Salvar alterações'"
            submit
        />
    </form>
</div>
